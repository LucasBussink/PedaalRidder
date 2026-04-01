<?php
session_start();
require_once '../src/authentication.php';
$auth = new Authenticate();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  header('Location: login.php');
  exit();
}

if (!isset($_SESSION['is_admin'])) {
  $_SESSION['is_admin'] = $auth->checkIsAdmin($_SESSION['email'] ?? '');
}

if ($_SESSION['is_admin'] !== true) {
  header('Location: index.php');
  exit();
}

require_once '../src/planning.php';
require_once '../src/appointments.php';

$planning = new Planning();
$appointments = new Appointments();
$unplannedCount = count($appointments->getUnplannedAppointments());

$selectedDateRaw = $_GET['datum'] ?? date('Y-m-d');
$selectedTimestamp = strtotime($selectedDateRaw);

if ($selectedTimestamp === false) {
    $selectedTimestamp = strtotime(date('Y-m-d'));
}

$selectedMonday = date('Y-m-d', strtotime('monday this week', $selectedTimestamp));
$days = $planning->getWorkweekDays($selectedMonday);

$previousWeekDate = date('Y-m-d', strtotime($selectedMonday . ' -7 days'));
$nextWeekDate = date('Y-m-d', strtotime($selectedMonday . ' +7 days'));

$currentMonday = date('Y-m-d', strtotime('monday this week'));
$isCurrentWeek = $selectedMonday === $currentMonday;

$weekNumber = date('W', strtotime($selectedMonday));

if ($unplannedCount === 0) {
    $unplannedMessage = 'Er zijn geen afspraken om in te plannen.';
} elseif ($unplannedCount === 1) {
    $unplannedMessage = 'Er moet nog 1 afspraak ingepland worden.';
} else {
    $unplannedMessage = 'Er moeten nog ' . $unplannedCount . ' afspraken ingepland worden.';
}

function formatDutchDay($date)
{
    $dutchDayNames = [
        1 => 'Maandag',
        2 => 'Dinsdag',
        3 => 'Woensdag',
        4 => 'Donderdag',
        5 => 'Vrijdag',
        6 => 'Zaterdag',
        7 => 'Zondag',
    ];

    $dayNumber = (int) date('N', strtotime($date));
    return $dutchDayNames[$dayNumber] . ' ' . date('d-m-y', strtotime($date));
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/adminAgenda.css">
</head>

<body>
  <header>
    <div class="container">
      <a href="index.php" class="logo">
        <img src="assets/images/logo-transparent.png" alt="website logo">
        <span class="orange">De</span>
        <span class="blue">Pedaal</span>
        <span class="orange">Ridder</span>
      </a>

      <nav>
        <?php
        if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
            echo '<a href="afspraken_inplannen.php" class="appointments-button">Afspraken Inplannen</a>';
            echo '<a href="basic_repairs.php" class="repairs-button">Standaard Reparaties</a>';
            echo '<a href="customer_manage.php" class="customers-button">Klanten</a>';
            echo '<a href="logout.php" class="logout-button">Uitloggen</a>';
        } else {
          echo '<a href="login.php" class="login-button">Inloggen</a>';
          echo '<a href="registreer.php" class="register-button">Registreren</a>';
        }
        ?>
      </nav>
    </div>
  </header>

    <main>
        <section class="admin-agenda">
            <div class="week-nav">
                <a href="?datum=<?php echo urlencode($previousWeekDate); ?>">&larr; Vorige week</a>
                <span class="week-info">
                    Week <?php echo htmlspecialchars($weekNumber); ?>
                    <?php if ($isCurrentWeek): ?>
                        - huidige week
                    <?php endif; ?>
                </span>
                <a href="?datum=<?php echo urlencode($nextWeekDate); ?>">Volgende week &rarr;</a>
            </div>

            <div class="agenda-table-wrapper">
                <table class="agenda-table">
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <th><?php echo htmlspecialchars(formatDutchDay($day)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <td>
                                <?php
                                $repairs = $planning->getRepairsForDay($day);

                                if (count($repairs) > 0) {
                                    foreach ($repairs as $repair) {
                                        $startTime = date('H:i', strtotime($repair['begintime']));
                                        $endTime = date('H:i', strtotime($repair['endtime']));

                                        $repairDetails = [
                                            'id' => $repair['id'] ?? '',
                                            'datum' => date('d-m-Y', strtotime($repair['begintime'])),
                                            'tijd' => $startTime . ' - ' . $endTime,
                                            'klant' => $repair['customer_name'] ?? 'Onbekend',
                                            'type' => $repair['type'] ?? '',
                                            'status' => $repair['status'] ?? '',
                                            'merk' => $repair['brand'] ?? '',
                                            'model' => $repair['model'] ?? '',
                                            'omschrijving' => $repair['description'] ?? '',
                                            'foto' => $repair['photo_path'] ?? '',
                                        ];

                                        $repairJson = htmlspecialchars(json_encode($repairDetails), ENT_QUOTES, 'UTF-8');

                                        echo "<button type='button' class='appointment-card' data-appointment='" . $repairJson . "'>";
                                        echo '<b>' . htmlspecialchars($startTime . ' - ' . $endTime) . '</b><br>';
                                        echo 'Soort: ' . htmlspecialchars($repair['type']) . '<br>';
                                        echo 'Status: ' . htmlspecialchars($repair['status']);
                                        echo '</button>';
                                    }
                                } else {
                                    echo '<span class="agenda-empty">Geen reparaties</span>';
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </div>

            <div class="unplanned-bar<?php echo $unplannedCount === 0 ? ' is-empty' : ''; ?>">
                <?php echo htmlspecialchars($unplannedMessage); ?>
            </div>
            
        </section>

<div id="appointment-modal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <button type="button" class="modal-close" id="modal-close" aria-label="Sluiten">&times;</button>
        <h2 id="modal-title">Afspraak details</h2>
        <form id="modal-edit-form" class="modal-grid">
            <span id="modal-id" style="display:none;"></span>
            <strong>Datum</strong><input type="date" id="modal-date-input" name="date" required>
            <strong>Begintijd</strong><input type="time" id="modal-starttime-input" name="starttime" required>
            <strong>Eindtijd</strong><input type="time" id="modal-endtime-input" name="endtime" required>
            <strong>Klant</strong><span id="modal-customer"></span>
            <strong>Type</strong><span id="modal-type"></span>
            <strong>Status</strong>
            <select id="modal-status-input" name="status" required>
                <option value="gepland">Gepland</option>
                <option value="klaar">Klaar</option>
                <option value="bezig">Bezig</option>
                <option value="geannuleerd">Geannuleerd</option>
                <option value="opgehaald">Opgehaald</option>
            </select>
            <strong>Merk</strong><span id="modal-brand"></span>
            <strong>Model</strong><span id="modal-model"></span>
            <strong>Omschrijving</strong><span id="modal-description"></span>
            <strong>Foto</strong><span id="modal-photo"></span>
            <div class="modal-actions">
                <button type="submit" id="modal-edit" class="modal-action-edit">Wijzig afspraak</button>
                <button type="button" id="modal-cancel" class="modal-action-cancel">Annuleer afspraak</button>
                <button type="button" id="modal-noshow" class="modal-action-noshow" style="display:none;">No Show</button>
                <button type="button" id="modal-done" class="modal-action-done">Voltooid</button>
            </div>
        </form>
    </div>
</div>
  </main>
</body>

</html>
<script>
    const modal = document.getElementById('appointment-modal');
    const closeModalButton = document.getElementById('modal-close');

    function setModalText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {
                el.value = value || '';
            } else {
                el.textContent = value && value.trim() !== '' ? value : '-';
            }
        }
    }

    function openAppointmentModal(appointment) {
        document.getElementById('modal-id').textContent = String(appointment.id || '');
        // datum: dd-mm-YYYY
        if (appointment.datum) {
            const [dag, maand, jaar] = appointment.datum.split('-');
            setModalText('modal-date-input', `${jaar}-${maand}-${dag}`);
        } else {
            setModalText('modal-date-input', '');
        }
        // tijd: HH:MM - HH:MM
        if (appointment.tijd) {
            const tijden = appointment.tijd.split(' - ');
            setModalText('modal-starttime-input', tijden[0] || '');
            setModalText('modal-endtime-input', tijden[1] || '');
        } else {
            setModalText('modal-starttime-input', '');
            setModalText('modal-endtime-input', '');
        }
        setModalText('modal-customer', String(appointment.klant || ''));
        setModalText('modal-type', String(appointment.type || ''));
        setModalText('modal-status-input', String(appointment.status || ''));
        setModalText('modal-brand', String(appointment.merk || ''));
        setModalText('modal-model', String(appointment.model || ''));
        setModalText('modal-description', String(appointment.omschrijving || ''));

        const photoElement = document.getElementById('modal-photo');
        photoElement.innerHTML = '';
        if (appointment.foto && appointment.foto.trim() !== '') {
            const image = document.createElement('img');
            image.src = appointment.foto;
            image.alt = 'Reparatiefoto';
            image.className = 'modal-photo';
            photoElement.appendChild(image);
        } else {
            photoElement.textContent = '-';
        }

        // Toon No Show knop alleen als eindtijd in het verleden ligt
        const noShowButton = document.getElementById('modal-noshow');
        if (noShowButton) {
            let eindtijd = appointment.tijd ? appointment.tijd.split(' - ')[1] : null;
            let datum = appointment.datum;
            if (eindtijd && datum) {
                // datum: dd-mm-YYYY, tijd: HH:MM
                const [dag, maand, jaar] = datum.split('-');
                const eindDateTime = new Date(`${jaar}-${maand}-${dag}T${eindtijd}:00`);
                if (!isNaN(eindDateTime.getTime()) && eindDateTime < new Date()) {
                    noShowButton.style.display = '';
                } else {
                    noShowButton.style.display = 'none';
                }
            } else {
                noShowButton.style.display = 'none';
            }
        }
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeAppointmentModal() {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.appointment-card').forEach((card) => {
        card.addEventListener('click', () => {
            const payload = card.getAttribute('data-appointment');
            if (!payload) {
                return;
            }

            try {
                const appointment = JSON.parse(payload);
                openAppointmentModal(appointment);
            } catch (error) {
                console.error('Kon afspraakdetails niet openen', error);
            }
        });
    });

    closeModalButton.addEventListener('click', closeAppointmentModal);

        // Annuleer knop event handler

        const cancelButton = document.getElementById('modal-cancel');
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                const id = document.getElementById('modal-id').textContent.trim();
                if (!id) {
                    alert('Geen afspraak geselecteerd.');
                    return;
                }
                if (!confirm('Weet je zeker dat je deze afspraak wilt annuleren?')) {
                    return;
                }
                fetch('appointment_delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Afspraak verwijderd!');
                        window.location.reload();
                    } else {
                        alert('Fout bij verwijderen: ' + (data.message || 'Onbekende fout'));
                    }
                })
                .catch(() => alert('Netwerkfout bij verwijderen.'));
            });
        }

        // No Show knop event handler
        const noShowButton = document.getElementById('modal-noshow');
        if (noShowButton) {
            noShowButton.addEventListener('click', function() {
                const id = document.getElementById('modal-id').textContent.trim();
                const date = document.getElementById('modal-date-input').value;
                const start = document.getElementById('modal-starttime-input').value;
                const end = document.getElementById('modal-endtime-input').value;
                if (!id || !date || !start || !end) {
                    alert('Vul alle velden in.');
                    return;
                }
                const begintime = `${date} ${start}:00`;
                const endtime = `${date} ${end}:00`;
                fetch('appointment_edit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, begintime, endtime, status: 'niet opgehaald' })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Status gewijzigd naar niet opgehaald!');
                        window.location.reload();
                    } else {
                        alert('Fout bij bijwerken: ' + (data.message || 'Onbekende fout'));
                    }
                })
                .catch(() => alert('Netwerkfout bij bijwerken.'));
            });
        }

        // Voltooid knop event handler
        const doneButton = document.getElementById('modal-done');
        if (doneButton) {
            doneButton.addEventListener('click', function() {
                const id = document.getElementById('modal-id').textContent.trim();
                const date = document.getElementById('modal-date-input').value;
                const start = document.getElementById('modal-starttime-input').value;
                const end = document.getElementById('modal-endtime-input').value;
                if (!id || !date || !start || !end) {
                    alert('Vul alle velden in.');
                    return;
                }
                const begintime = `${date} ${start}:00`;
                const endtime = `${date} ${end}:00`;
                fetch('appointment_edit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, begintime, endtime, status: 'klaar' })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Status gewijzigd naar klaar!');
                        window.location.reload();
                    } else {
                        alert('Fout bij bijwerken: ' + (data.message || 'Onbekende fout'));
                    }
                })
                .catch(() => alert('Netwerkfout bij bijwerken.'));
            });
        }

        // Wijzig formulier submit handler
        const editForm = document.getElementById('modal-edit-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('modal-id').textContent.trim();
                const date = document.getElementById('modal-date-input').value;
                const start = document.getElementById('modal-starttime-input').value;
                const end = document.getElementById('modal-endtime-input').value;
                const status = document.getElementById('modal-status-input').value;
                if (!id || !date || !start || !end || !status) {
                    alert('Vul alle velden in.');
                    return;
                }
                // Combineer naar MySQL datetime formaat
                const begintime = `${date} ${start}:00`;
                const endtime = `${date} ${end}:00`;
                fetch('appointment_edit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, begintime, endtime, status })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Afspraak bijgewerkt!');
                        window.location.reload();
                    } else {
                        alert('Fout bij bijwerken: ' + (data.message || 'Onbekende fout'));
                    }
                })
                .catch(() => alert('Netwerkfout bij bijwerken.'));
            });
        }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeAppointmentModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeAppointmentModal();
        }
    });
</script>