<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  header("Location: login.php");
  exit();
}

require_once '../src/appointments.php';
require_once '../src/mailer.php';

$appointmentsModel = new Appointments();
$error = '';
$success = '';
$mail_message = '';
$web3forms_payload = null;

// Haal het ID op uit de URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
  header("Location: afspraken_inplannen.php");
  exit();
}

// Haal de afspraakgegevens op
$appointment = $appointmentsModel->getById($id);
if ($appointment === null) {
  header("Location: afspraken_inplannen.php");
  exit();
}

// Verwerk het formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $date    = $_POST['date'] ?? '';
  $time    = $_POST['time'] ?? '';
  $minutes = (int) ($_POST['minutes'] ?? 0);

  if ($date === '' || $time === '' || $minutes <= 0) {
    $error = 'Vul alle velden in en zorg dat de duur groter dan 0 is.';
  } else {
    // Bereken begin- en eindtijd
    $begin = new DateTime($date . ' ' . $time);
    $end   = (clone $begin)->modify("+{$minutes} minutes");

    $ruleViolation = $appointmentsModel->getSlotRuleViolation(
      $begin->format('Y-m-d H:i:s'),
      $end->format('Y-m-d H:i:s')
    );

    if ($ruleViolation !== null) {
      $error = $ruleViolation;
    }
    // Controleer of het slot vrij is (eigen afspraak wordt uitgesloten)
    else if (!$appointmentsModel->isSlotFree($begin->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $id)) {
      $error = 'Dit tijdslot overlapt met een andere afspraak. Kies een ander moment.';
    } else {
      // Sla het tijdslot op
      $appointmentsModel->planAppointment($id, $begin->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));
      $success = 'Afspraak ingepland van ' . $begin->format('d-m-Y H:i') . ' tot ' . $end->format('H:i') . '.';

      // Herlaad de afspraakdata zodat de pagina de nieuwe tijden toont
      $appointment = $appointmentsModel->getById($id);

      // Mailbevestiging voorbereiden (client-side Web3Forms).
      if (!empty($appointment['customer_email'])) {
        $accessKey = envValue('WEB3FORMS_ACCESS_KEY');
        $subject = envValue('WEB3FORMS_SUBJECT', 'Uw afspraak is ingepland');

        if ($accessKey === '' || $accessKey === 'YOUR_WEB3FORMS_ACCESS_KEY') {
          $accessKey = '8718a5ba-c674-4d04-a282-10462d15f8fb';
        }

        if ($accessKey === '') {
          $mail_message = 'Mail niet verstuurd: WEB3FORMS_ACCESS_KEY ontbreekt.';
        } else {
          $slotText = $begin->format('d-m-Y H:i') . ' t/m ' . $end->format('H:i');

          $bericht = "Uw afspraak is ingepland door de fietsenmaker.\n";
          $bericht .= "Tijdslot: {$slotText}\n\n";
          $bericht .= "Type reparatie: " . ($appointment['type'] ?? '-') . "\n";
          $bericht .= "Fiets: " . ($appointment['brand'] ?? '-') . " " . ($appointment['model'] ?? '-') . "\n";

          $klantNaam = (string) ($appointment['customer_name'] ?? 'Klant');
          $klantEmail = (string) ($appointment['customer_email'] ?? '');

          $web3forms_payload = [
            'access_key' => $accessKey,
            'from_name' => 'De PedaalRidder Werkplaats',
            'subject' => $subject,
            'name' => $klantNaam,
            'email' => $klantEmail,
            'message' => $bericht,
            'replyto' => 'info@pedaalridder.nl',
            'botcheck' => '',
            'autoresponse' => "Beste {$klantNaam},\n\nUw afspraak is ingepland door de fietsenmaker.\nTijdslot: {$slotText}\n\nTot dan!\nDe PedaalRidder",
          ];

          $mail_message = 'Mail wordt verzonden...';
        }
      }
    }
  }
}

// Gekozen datum voor bezette-slots-overzicht (default vandaag)
$selectedDate = $_POST['date'] ?? date('Y-m-d');
$occupiedSlots = $appointmentsModel->getOccupiedSlotsForDate($selectedDate);
?>
<!DOCTYPE html>
<html lang="nl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Afspraak Inplannen | De PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      font-family: Inter, sans-serif;
      max-width: 700px;
      margin: 40px auto;
      padding: 0 20px;
    }

    h1 {
      color: #136DEC;
    }

    .card {
      background: #f5f5f5;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 24px;
    }

    .card h2 {
      margin: 0 0 12px;
      font-size: 1rem;
      color: #444;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    td {
      padding: 6px 10px;
    }

    td:first-child {
      font-weight: 600;
      width: 40%;
    }

    .photo-preview {
      max-width: 100%;
      max-height: 220px;
      border-radius: 4px;
      margin-top: 8px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 6px;
    }

    input[type="date"],
    input[type="time"],
    input[type="number"] {
      width: 100%;
      padding: 9px 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      margin-bottom: 16px;
    }

    input:focus {
      outline: none;
      border-color: #136DEC;
      box-shadow: 0 0 4px rgba(19, 109, 236, .3);
    }

    button[type="submit"] {
      background: #136DEC;
      color: #fff;
      border: none;
      padding: 11px 22px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
    }

    button[type="submit"]:hover {
      background: #0d56c4;
    }

    .msg-ok {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      padding: 12px;
      border-radius: 4px;
      margin-bottom: 16px;
    }

    .msg-err {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      padding: 12px;
      border-radius: 4px;
      margin-bottom: 16px;
    }

    .occupied {
      margin: 0;
      padding: 0;
      list-style: none;
    }

    .occupied li {
      background: #fff3cd;
      border: 1px solid #ffc107;
      border-radius: 4px;
      padding: 5px 10px;
      margin-bottom: 5px;
      font-size: .9rem;
    }

    a.back {
      display: inline-block;
      margin-top: 20px;
      color: #136DEC;
      text-decoration: none;
    }

    a.back:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <h1>Afspraak Inplannen</h1>

  <?php if ($success !== ''): ?>
    <div class="msg-ok"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="msg-err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($mail_message !== ''): ?>
    <div id="mail-status" class="<?= (strpos($mail_message, 'Mail niet verstuurd') === 0) ? 'msg-err' : 'msg-ok' ?>">
      <?= htmlspecialchars($mail_message) ?>
    </div>
  <?php endif; ?>

  <!-- Klant- en reparatiegegevens -->
  <div class="card">
    <h2>Reparatiegegevens</h2>
    <table>
      <tr>
        <td>Klant</td>
        <td><?= htmlspecialchars($appointment['customer_name']) ?></td>
      </tr>
      <tr>
        <td>Merk</td>
        <td><?= htmlspecialchars($appointment['brand']) ?></td>
      </tr>
      <tr>
        <td>Model</td>
        <td><?= htmlspecialchars($appointment['model']) ?></td>
      </tr>
      <tr>
        <td>Type reparatie</td>
        <td><?= htmlspecialchars($appointment['type']) ?></td>
      </tr>
      <tr>
        <td>Omschrijving</td>
        <td><?= htmlspecialchars($appointment['description']) ?></td>
      </tr>
      <?php if (!empty($appointment['begintime'])): ?>
        <tr>
          <td>Huidig slot</td>
          <td>
            <?= (new DateTime($appointment['begintime']))->format('d-m-Y H:i') ?>
            &ndash;
            <?= (new DateTime($appointment['endtime']))->format('H:i') ?>
          </td>
        </tr>
      <?php endif; ?>
    </table>
    <?php if (!empty($appointment['photo_path'])): ?>
      <p style="margin-top:12px;"><strong>Foto:</strong></p>
      <a href="<?= htmlspecialchars($appointment['photo_path']) ?>" target="_blank">
        <img src="<?= htmlspecialchars($appointment['photo_path']) ?>" alt="Reparatiefoto" class="photo-preview">
      </a>
    <?php endif; ?>
  </div>

  <!-- Bezette tijdslots op gekozen dag -->
  <div class="card">
    <h2>Bezette tijdslots op <span id="slot-date-label"><?= htmlspecialchars($selectedDate) ?></span></h2>
    <?php if (empty($occupiedSlots)): ?>
      <p>Geen afspraken op deze dag.</p>
    <?php else: ?>
      <ul class="occupied">
        <?php foreach ($occupiedSlots as $slot): ?>
          <?php
          $slotBegin = new DateTime($slot['begintime']);
          $slotEind  = new DateTime($slot['endtime']);
          ?>
          <li><?= $slotBegin->format('H:i') ?> &ndash; <?= $slotEind->format('H:i') ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Inplanformulier -->
  <div class="card">
    <h2>Nieuw tijdslot instellen</h2>
    <form method="POST">
      <label for="date">Datum</label>
      <input type="date" id="date" name="date"
        value="<?= htmlspecialchars($selectedDate) ?>"
        min="<?= date('Y-m-d') ?>" required>

      <label for="time">Begintijd</label>
      <input type="time" id="time" name="time"
        value="<?= htmlspecialchars($_POST['time'] ?? '08:30') ?>"
        min="08:30" max="17:30" required>

      <label for="minutes">Duur (in minuten)</label>
      <input type="number" id="minutes" name="minutes"
        value="<?= htmlspecialchars($_POST['minutes'] ?? '60') ?>"
        min="5" max="480" step="5" required>

      <p id="end-time-preview" style="color:#136DEC; margin-bottom:16px;"></p>

      <button type="submit">Afspraak Inplannen</button>
    </form>
  </div>

  <a class="back" href="afspraken_inplannen.php">&larr; Terug naar overzicht</a>

  <script>
    // Bereken live de eindtijd op basis van begintijd + duur
    const dateInput = document.getElementById('date');
    const timeInput = document.getElementById('time');
    const minutesInput = document.getElementById('minutes');
    const preview = document.getElementById('end-time-preview');

    function updateEndTimePreview() {
      const time = timeInput.value;
      const minutes = parseInt(minutesInput.value) || 0;

      if (!time || minutes <= 0) {
        preview.textContent = '';
        return;
      }

      const [h, m] = time.split(':').map(Number);
      const total = h * 60 + m + minutes;
      const endH = String(Math.floor(total / 60)).padStart(2, '0');
      const endM = String(total % 60).padStart(2, '0');
      preview.textContent = `Eindtijd: ${endH}:${endM}`;
    }

    timeInput.addEventListener('input', updateEndTimePreview);
    minutesInput.addEventListener('input', updateEndTimePreview);
    updateEndTimePreview();

    // Laad de pagina opnieuw met de nieuwe datum om bezette slots te tonen
    dateInput.addEventListener('change', function() {
      const url = new URL(window.location.href);
      const form = document.querySelector('form');
      // Stuur het formulier via GET met alleen de datum zodat de bezette slots herladen
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = '_date_preview';
      hidden.value = '1';
      form.appendChild(hidden);
      form.method = 'POST';
      form.submit();
    });
  </script>

  <?php if (!empty($web3forms_payload)): ?>
    <script>
      (function() {
        var statusEl = document.getElementById('mail-status');
        var payload = <?= json_encode($web3forms_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function postPayload(data) {
          return fetch('https://api.web3forms.com/submit', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data).toString()
          }).then(function(response) {
            return response.json();
          });
        }

        function buildSpamSafeRetryPayload(original) {
          var retry = Object.assign({}, original);
          retry.subject = 'Afspraakbevestiging De PedaalRidder';
          retry.message = 'Afspraak bevestigd. Tijdslot: ' + (new Date()).toLocaleString('nl-NL');
          retry.autoresponse = 'Uw afspraak is ingepland door De PedaalRidder. Controleer uw klantportaal of neem contact op bij vragen.';
          return retry;
        }

        postPayload(payload)
          .then(function(data) {
            if (!statusEl) {
              return;
            }

            if (data && data.success) {
              statusEl.className = 'msg-ok';
              statusEl.textContent = 'Mail verstuurd: ' + (data.message || 'Verzending gelukt.');
              return;
            }

            var message = (data && data.message) ? String(data.message) : 'Onbekende fout.';
            var isSpamBlocked = message.toLowerCase().indexOf('marked as spam') !== -1;

            if (!isSpamBlocked) {
              statusEl.className = 'msg-err';
              statusEl.textContent = 'Mail niet verstuurd: ' + message;
              return;
            }

            statusEl.className = 'msg-err';
            statusEl.textContent = 'Mail werd als spam gezien. Nieuwe poging met veilig bericht...';

            return postPayload(buildSpamSafeRetryPayload(payload)).then(function(retryData) {
              if (!statusEl) {
                return;
              }

              if (retryData && retryData.success) {
                statusEl.className = 'msg-ok';
                statusEl.textContent = 'Mail verstuurd na tweede poging.';
              } else {
                statusEl.className = 'msg-err';
                statusEl.textContent = 'Mail niet verstuurd: ' + ((retryData && retryData.message) ? retryData.message : 'Onbekende fout.');
              }
            });
          })
          .catch(function() {
            if (!statusEl) {
              return;
            }

            statusEl.className = 'msg-err';
            statusEl.textContent = 'Mail niet verstuurd: netwerkfout tijdens verzenden.';
          });
      })();
    </script>
  <?php endif; ?>
</body>

</html>