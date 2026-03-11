<?php

require_once __DIR__ . '/database.php';

class Appointments extends Database
{
  // Lees alle kolommen van de appointments tabel, zodat we flexibel met verschillende kolomnamen kunnen omgaan.
  private function getAppointmentColumns()
  {
    $query = "SELECT COLUMN_NAME
              FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appointments'";

    $rows = parent::voerQueryUit($query);
    return array_map(static fn($row) => $row['COLUMN_NAME'], $rows);
  }

  // Pak de eerste kolomnaam die bestaat uit een lijst met mogelijke namen.
  private function pickFirstExistingColumn($columns, $candidates)
  {
    foreach ($candidates as $candidate) {
      if (in_array($candidate, $columns, true)) {
        return $candidate;
      }
    }
    return null;
  }

  // Haal de duur van een reparatie op uit basic_repairs.minutes.
  // Als er niets gevonden is, gebruiken we 60 minuten als veilige fallback.
  private function getRepairDurationMinutes($basicRepairId)
  {
    $query = "SELECT minutes
              FROM basic_repairs
              WHERE id = ?
              LIMIT 1";

    $rows = parent::voerQueryUit($query, [$basicRepairId]);

    if (empty($rows) || !isset($rows[0]['minutes'])) {
      return 60;
    }

    $minutes = (int) $rows[0]['minutes'];
    return $minutes > 0 ? $minutes : 60;
  }

  private function ensureSchedulingColumnsExist()
  {
    $columns = $this->getAppointmentColumns();

    $startColumn = $this->pickFirstExistingColumn($columns, ['begintime', 'appointment_start', 'start_time', 'scheduled_start', 'start_datetime']);
    $endColumn = $this->pickFirstExistingColumn($columns, ['endtime', 'appointment_end', 'end_time', 'scheduled_end', 'end_datetime']);

    // Als er geen startkolom bestaat, maken we die automatisch aan.
    if ($startColumn === null) {
      parent::voerQueryUit("ALTER TABLE appointments ADD COLUMN appointment_start DATETIME NULL");
      parent::voerQueryUit("ALTER TABLE appointments ADD INDEX idx_appointment_start (appointment_start)");
      $startColumn = 'appointment_start';
    }

    // Als er geen eindkolom bestaat, maken we die automatisch aan.
    if ($endColumn === null) {
      parent::voerQueryUit("ALTER TABLE appointments ADD COLUMN appointment_end DATETIME NULL");
      parent::voerQueryUit("ALTER TABLE appointments ADD INDEX idx_appointment_end (appointment_end)");
      $endColumn = 'appointment_end';
    }

    return [$startColumn, $endColumn];
  }

  private function alignToNextSlot(DateTime $dateTime, $stepMinutes = 30)
  {
    $minute = (int) $dateTime->format('i');
    $remainder = $minute % $stepMinutes;

    if ($remainder !== 0) {
      $dateTime->modify('+' . ($stepMinutes - $remainder) . ' minutes');
    }

    $dateTime->setTime((int) $dateTime->format('H'), (int) $dateTime->format('i'), 0);
    return $dateTime;
  }

  private function hasOverlap($startColumn, $endColumn, DateTime $start, DateTime $end)
  {
    $query = "SELECT COUNT(*) AS overlap_count
              FROM appointments
              WHERE status <> 'geannuleerd'
              AND {$startColumn} < ?
              AND {$endColumn} > ?";

    $result = parent::voerQueryUit($query, [
      $end->format('Y-m-d H:i:s'),
      $start->format('Y-m-d H:i:s')
    ]);

    return isset($result[0]['overlap_count']) && (int) $result[0]['overlap_count'] > 0;
  }

  private function findNextAvailableSlot($startColumn, $endColumn, $durationMinutes = 60)
  {
    $workdayStartHour = 9;
    $workdayEndHour = 17;
    $stepMinutes = 30;
    $maxDaysAhead = 45;

    $candidate = $this->alignToNextSlot(new DateTime(), $stepMinutes);

    for ($day = 0; $day <= $maxDaysAhead; $day++) {
      $currentDay = (clone $candidate)->modify("+{$day} day");
      $weekday = (int) $currentDay->format('N');

      if ($weekday >= 6) {
        continue;
      }

      $startOfDay = (clone $currentDay)->setTime($workdayStartHour, 0, 0);
      $endOfDay = (clone $currentDay)->setTime($workdayEndHour, 0, 0);

      if ($day === 0 && $candidate > $startOfDay) {
        $startOfDay = $this->alignToNextSlot(clone $candidate, $stepMinutes);
      }

      for ($slot = clone $startOfDay; $slot < $endOfDay; $slot->modify("+{$stepMinutes} minutes")) {
        $slotEnd = (clone $slot)->modify("+{$durationMinutes} minutes");

        if ($slotEnd > $endOfDay) {
          break;
        }

        if (!$this->hasOverlap($startColumn, $endColumn, $slot, $slotEnd)) {
          return [
            'start' => $slot,
            'end' => $slotEnd
          ];
        }
      }
    }

    throw new Exception('Geen vrij tijdslot beschikbaar binnen 45 dagen.');
  }

  public function addAppointment($customerId, $basicRepairId, $brand, $model, $typeRepair, $issue, $photoPath = null, $status = 'gepland')
  {
    $columns = $this->getAppointmentColumns();

    $startColumn = $this->pickFirstExistingColumn($columns, ['begintime', 'appointment_start', 'start_time', 'scheduled_start', 'start_datetime']);
    $endColumn = $this->pickFirstExistingColumn($columns, ['endtime', 'appointment_end', 'end_time', 'scheduled_end', 'end_datetime']);

    $scheduledStart = null;
    $scheduledEnd = null;

    if ($basicRepairId !== null) {
      // Zorg dat tijdslotkolommen bestaan voordat we plannen.
      if ($startColumn === null || $endColumn === null) {
        [$startColumn, $endColumn] = $this->ensureSchedulingColumnsExist();
      }

      // Gebruik de duur (in minuten) uit basic_repairs.minutes.
      $durationMinutes = $this->getRepairDurationMinutes($basicRepairId);

      // Zoek het eerstvolgende vrije slot dat NIET overlapt met bestaande afspraken.
      $slot = $this->findNextAvailableSlot($startColumn, $endColumn, $durationMinutes);
      $scheduledStart = $slot['start'];
      $scheduledEnd = $slot['end'];
    } else {
      // Overig (geen basicRepairId): eerst handmatige inschatting, dus nog geen tijdslot.
      // Status blijft 'gepland' omdat de ENUM in de database alleen deze vaste waarden toestaat:
      // 'gepland', 'klaar', 'bezig', 'geannuleerd', 'opgehaald'
      $status = 'gepland';
    }

    $insertColumns = ['customer_id', 'basic_repair_id', 'brand', 'model', 'type', 'description', 'photo_path', 'status'];
    $insertValues = [$customerId, $basicRepairId, $brand, $model, $typeRepair, $issue, $photoPath, $status];

    if ($scheduledStart !== null && $scheduledEnd !== null) {
      $insertColumns[] = $startColumn;
      $insertValues[] = $scheduledStart->format('Y-m-d H:i:s');

      $insertColumns[] = $endColumn;
      $insertValues[] = $scheduledEnd->format('Y-m-d H:i:s');
    }

    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $query = 'INSERT INTO appointments (' . implode(', ', $insertColumns) . ') VALUES (' . $placeholders . ')';

    parent::voerQueryUit($query, $insertValues);

    return [
      'scheduled_start' => $scheduledStart?->format('Y-m-d H:i:s'),
      'scheduled_end' => $scheduledEnd?->format('Y-m-d H:i:s'),
      'status' => $status
    ];
  }

  // Haal één afspraak op via ID, inclusief klantnaam en foto.
  public function getById($id)
  {
    $query = "SELECT a.*, c.name AS customer_name
              FROM appointments AS a
              JOIN customers AS c ON a.customer_id = c.id
              WHERE a.id = ?
              LIMIT 1";

    $rows = parent::voerQueryUit($query, [(int) $id]);
    return $rows[0] ?? null;
  }

  // Geeft alle bezette tijdslots terug voor een gegeven datum (YYYY-MM-DD),
  // zodat de pagina ze geblokkeerd kan tonen.
  public function getOccupiedSlotsForDate($date)
  {
    $query = "SELECT begintime, endtime
              FROM appointments
              WHERE status <> 'geannuleerd'
              AND DATE(begintime) = ?
              AND begintime IS NOT NULL";

    return parent::voerQueryUit($query, [$date]);
  }

  // Controleer of een gewenst tijdslot vrij is (exclusief de afspraak zelf via $excludeId).
  public function isSlotFree($begin, $end, $excludeId = null)
  {
    $query = "SELECT COUNT(*) AS overlap_count
              FROM appointments
              WHERE status <> 'geannuleerd'
              AND begintime IS NOT NULL
              AND begintime < ?
              AND endtime > ?";

    $params = [$end, $begin];

    // Sluit de huidige afspraak uit (zodat je hem kunt herplannen zonder conflict met zichzelf).
    if ($excludeId !== null) {
      $query .= " AND id <> ?";
      $params[] = (int) $excludeId;
    }

    $result = parent::voerQueryUit($query, $params);
    return (int) $result[0]['overlap_count'] === 0;
  }

  // Sla het gekozen tijdslot op voor een bestaande afspraak.
  public function planAppointment($id, $begintime, $endtime)
  {
    $query = "UPDATE appointments
              SET begintime = ?, endtime = ?, status = 'gepland'
              WHERE id = ?";

    return parent::voerQueryUit($query, [$begintime, $endtime, (int) $id]);
  }

  public function getUnplannedAppointments()
  {
    $query = "SELECT a.id, c.name AS customer_name, a.brand, a.model, a.type, a.description
              FROM appointments AS a
              JOIN customers AS c ON a.customer_id = c.id
              WHERE a.begintime IS NULL
              AND a.endtime IS NULL
              AND a.status = 'gepland'
              ORDER BY a.id DESC";

    return parent::voerQueryUit($query);
  }
}
