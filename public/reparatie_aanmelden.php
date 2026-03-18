<?php
session_start();
require_once '../src/database.php';
require_once '../src/repairs.php';
require_once '../src/customer.php';
require_once '../src/appointments.php';
require_once '../src/mailer.php';
//aanmeldformulier voor reparatie
$repair = new Repairs();
$customerModel = new Customer();
$appointmentsModel = new Appointments();

$success_message = '';
$error_message = '';
$mail_message = '';
$uploaded_photo_path = null;
$uploaded_photo_url = null;
$scheduled_slot_message = '';
$web3forms_payload = null;

// Haal reparatietypen uit database
$repair_types = [];
$repair_types = $repair->GetAllRepairTypes();

if (isset($_POST['aanmelden'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $brand = trim($_POST['brand'] ?? '');
  $model = trim($_POST['model'] ?? '');
  $typeRepairValue = trim($_POST['type_repair'] ?? '');
  $issue = trim($_POST['issue'] ?? '');

  if ($name === '' || $email === '' || $phone === '' || $brand === '' || $typeRepairValue === '') {
    $error_message = 'Vul alle verplichte velden in.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = 'Vul een geldig e-mailadres in.';
  } else {
    try {
      $typeRepairName = $typeRepairValue;

      if (strtolower($typeRepairValue) !== 'overig') {
        foreach ($repair_types as $type) {
          if ((string) $type['id'] === (string) $typeRepairValue) {
            $typeRepairName = $type['name'];
            break;
          }
        }
      }

      $photoPath = null;
      $isOverig = strtolower($typeRepairValue) === 'overig';
      $hasPhoto = isset($_FILES['repair_photo']) && !empty($_FILES['repair_photo']['name']);

      if ($isOverig && !$hasPhoto) {
        throw new Exception('Voor Overig moet je een foto meesturen.');
      }

      if ($hasPhoto) {
        $uploadDir = __DIR__ . '/uploads/appointments/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['repair_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed, true)) {
          throw new Exception('Alleen JPG, JPEG, PNG, GIF of WEBP is toegestaan.');
        }

        $fileName = uniqid('repair_', true) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['repair_photo']['tmp_name'], $targetPath)) {
          throw new Exception('Uploaden van de afbeelding is mislukt.');
        }

        $photoPath = 'uploads/appointments/' . $fileName;
        $uploaded_photo_path = $photoPath;

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $photoRelative = ltrim($photoPath, '/');
        $photoUrlPath = ($scriptDir !== '' ? $scriptDir : '') . '/' . $photoRelative;
        $uploaded_photo_url = ($host !== '') ? ($scheme . '://' . $host . $photoUrlPath) : $photoUrlPath;
      }

      $customerId = $customerModel->findOrCreateCustomer($name, $email, $phone);

      $basicRepairId = strtolower($typeRepairValue) === 'overig' ? null : (int) $typeRepairValue;
      $status = 'gepland';

      $appointmentResult = $appointmentsModel->addAppointment(
        $customerId,
        $basicRepairId,
        $brand,
        $model,
        $typeRepairName,
        $issue,
        $photoPath,
        $status
      );

      $success_message = 'Reparatie succesvol aangemeld.';

      if (!empty($appointmentResult['scheduled_start']) && !empty($appointmentResult['scheduled_end'])) {
        $scheduled_slot_message = 'Tijdslot: ' . $appointmentResult['scheduled_start'] . ' t/m ' . $appointmentResult['scheduled_end'];
      } else {
        $scheduled_slot_message = 'Nog geen tijdslot ingepland (type Overig / eerst inspectie nodig).';
      }

      $accessKey = envValue('WEB3FORMS_ACCESS_KEY');
      $fromName = envValue('WEB3FORMS_FROM_NAME', 'De PedaalRidder Website');
      $subject = envValue('WEB3FORMS_SUBJECT', 'Nieuwe reparatie-aanmelding');

      // Fallback naar de key uit het bestaande contactformulier als .env nog placeholder is.
      if ($accessKey === '' || $accessKey === 'YOUR_WEB3FORMS_ACCESS_KEY') {
        $accessKey = '8718a5ba-c674-4d04-a282-10462d15f8fb';
      }

      if ($accessKey === '') {
        $mail_message = 'Mail niet verstuurd: WEB3FORMS_ACCESS_KEY ontbreekt of is nog placeholder.';
      } else {
        $slotText = $scheduled_slot_message;

        $bericht = "Nieuwe reparatie-aanmelding ontvangen:\n\n";
        $bericht .= "Naam: {$name}\n";
        $bericht .= "E-mail: {$email}\n";
        $bericht .= "Telefoon: {$phone}\n";
        $bericht .= "Merk: {$brand}\n";
        $bericht .= "Model: " . ($model !== '' ? $model : '-') . "\n";
        $bericht .= "Type reparatie: {$typeRepairName}\n";
        $bericht .= "Probleemomschrijving: " . ($issue !== '' ? $issue : '-') . "\n";
        $bericht .= "Tijdslot: {$slotText}\n";
        if (!empty($uploaded_photo_url)) {
          $bericht .= "Foto: {$uploaded_photo_url}\n";
        }

        $web3forms_payload = [
          'access_key' => $accessKey,
          'from_name' => 'De PedaalRidder',
          'subject' => $subject,
          'name' => $name,
          'email' => $email,
          'phone' => $phone,
          'message' => $bericht,
          'autoresponse' => "Beste {$name},\n\nUw reparatie-aanmelding bij De PedaalRidder is ontvangen.\n{$slotText}\n\nBedankt!",
        ];

        $mail_message = 'Mail wordt verzonden...';
      }

      $_POST = [];
    } catch (Exception $e) {
      $error_message = $e->getMessage();
    }
  }
}
?>
<h2>Klantgegevens</h2>
<form method="post" enctype="multipart/form-data">
  <label for="name">Naam:</label>
  <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required><br><br>

  <label for="email">E-mail:</label>
  <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required><br><br>

  <label for="phone">Telefoonnummer:</label>
  <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required><br><br>

  <h2>Fietsgegevens</h2>

  <label for="brand">Merk:</label>
  <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>" required><br><br>

  <label for="model">Model:</label>
  <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>"><br><br>

  <label for="type_repair">Type reparatie:</label>
  <select id="type_repair" name="type_repair">
    <option value="">-- Selecteer een reparatietype --</option>
    <?php if (!empty($repair_types)): ?>
      <?php foreach ($repair_types as $type): ?>
        <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo (isset($_POST['type_repair']) && (string)$_POST['type_repair'] === (string)$type['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($type['name']); ?>
        </option>
      <?php endforeach; ?>
      <option value="Overig" <?php echo (isset($_POST['type_repair']) && $_POST['type_repair'] === 'Overig') ? 'selected' : ''; ?>>Overig</option>
    <?php else: ?>
      <option value="">Geen reparatietypen gevonden</option>
    <?php endif; ?>
  </select><br><br>

  <div id="overig-extra" style="display:none;">
    <label for="repair_photo">Foto toevoegen (alleen bij Overig):</label><br>
    <input type="file" id="repair_photo" name="repair_photo" accept="image/*" style="display:none;">
    <button type="button" id="repair_photo_button">Kies afbeelding</button>
    <span id="repair_photo_name">Nog geen bestand gekozen</span><br><br>
  </div>

  <label for="issue">Probleemomschrijving:</label><br>
  <textarea id="issue" name="issue" rows="4" cols="50"><?php echo htmlspecialchars($_POST['issue'] ?? ''); ?></textarea><br><br>

  <input type="submit" value="Reparatie aanmelden" name="aanmelden">
</form>

<script src="script/script.js"></script>


<?php
if (!empty($success_message)) {
  echo '<p style="color: green;">' . htmlspecialchars($success_message) . '</p>';
}

if (!empty($scheduled_slot_message)) {
  echo '<p style="color: #136DEC;">' . htmlspecialchars($scheduled_slot_message) . '</p>';
}

if (!empty($mail_message)) {
  $mailColor = (strpos($mail_message, 'Mail verstuurd') === 0) ? 'green' : 'orange';
  if ($mail_message === 'Mail wordt verzonden...') {
    $mailColor = '#136DEC';
  }
  echo '<p id="mail-status" style="color: ' . htmlspecialchars($mailColor, ENT_QUOTES, 'UTF-8') . ';">' . htmlspecialchars($mail_message) . '</p>';
}

if (!empty($uploaded_photo_path)) {
  echo '<p><strong>Geüploade afbeelding:</strong> <a href="' . htmlspecialchars($uploaded_photo_path) . '" target="_blank">Open afbeelding</a></p>';
  echo '<p><img src="' . htmlspecialchars($uploaded_photo_path) . '" alt="Geüploade reparatiefoto" style="max-width: 320px; height: auto; border: 1px solid #ccc; padding: 4px;"></p>';
}

if (!empty($error_message)) {
  echo '<p style="color: red;">' . htmlspecialchars($error_message) . '</p>';
}

if (!empty($web3forms_payload)) {
  $payloadJson = json_encode($web3forms_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  echo '<script>
  (function () {
    var statusEl = document.getElementById("mail-status");
    var payload = ' . $payloadJson . ';

    fetch("https://api.web3forms.com/submit", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams(payload).toString()
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!statusEl) {
          return;
        }

        if (data && data.success) {
          statusEl.style.color = "green";
          statusEl.textContent = "Mail verstuurd: " + (data.message || "Verzending gelukt.");
        } else {
          statusEl.style.color = "orange";
          statusEl.textContent = "Mail niet verstuurd: " + ((data && data.message) ? data.message : "Onbekende fout.");
        }
      })
      .catch(function () {
        if (!statusEl) {
          return;
        }
        statusEl.style.color = "orange";
        statusEl.textContent = "Mail niet verstuurd: netwerkfout tijdens verzenden.";
      });
  })();
  </script>';
}
