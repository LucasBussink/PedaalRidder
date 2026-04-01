<?php
session_start();
require_once '../src/database.php';
require_once '../src/mailer.php';

$db = new Database();
$message = '';
$web3forms_payload = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_expires_at'], $_SESSION['verified']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['request_reset'])) {
    $email = trim($_POST['email'] ?? '');
    
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message = 'Vul een geldig e-mailadres in.';
    } else {
      $users = $db->voerQueryUit("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
    
      if (count($users) > 0) {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = time() + (15 * 60);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_expires_at'] = $expiresAt;
        unset($_SESSION['verified']);
      
        $accessKey = envValue('WEB3FORMS_ACCESS_KEY');
        if ($accessKey === '' || $accessKey === 'YOUR_WEB3FORMS_ACCESS_KEY') {
          $accessKey = '8718a5ba-c674-4d04-a282-10462d15f8fb';
        }

        $bericht = "Uw resetcode is: {$code}\n\nDeze code is 15 minuten geldig.";

        $web3forms_payload = [
          'access_key' => $accessKey,
          'from_name' => 'De PedaalRidder',
          'subject' => 'Uw resetcode',
          'name' => 'Klant',
          'email' => $email,
          'phone' => '',
          'message' => $bericht,
        ];

        $message = 'Code wordt verzonden...';
      } else {
        $message = 'E-mailadres niet gevonden.';
      }
    }
  }
  
  if (isset($_POST['verify_code'])) {
    $sessionEmail = (string) ($_SESSION['reset_email'] ?? '');
    $sessionCode = (string) ($_SESSION['reset_code'] ?? '');
    $expiresAt = (int) ($_SESSION['reset_expires_at'] ?? 0);
    $code = trim($_POST['code'] ?? '');

    if ($sessionEmail === '') {
      $message = 'Vraag eerst een resetcode aan.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
      $message = 'Vul een geldige 6-cijferige code in.';
    } elseif ($sessionCode === '' || $expiresAt <= 0) {
      $message = 'Geen actieve resetcode gevonden. Vraag opnieuw een code aan.';
    } elseif (time() > $expiresAt) {
      unset($_SESSION['reset_code'], $_SESSION['reset_expires_at'], $_SESSION['verified']);
      $message = 'Resetcode is verlopen. Vraag opnieuw een code aan.';
    } else {
      if (hash_equals($sessionCode, $code)) {
        $_SESSION['verified'] = true;
        unset($_SESSION['reset_code'], $_SESSION['reset_expires_at']);
        header('Location: set_new_password.php');
        exit();
      }

      $message = 'Ongeldige of verlopen code.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wachtwoord vergeten</title>
</head>
<body>
  <div id="mail-status"></div>

  <?php if (isset($message)): ?>
    <p><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <?php if (!isset($_SESSION['reset_code'])): ?>
    <form method="POST">
      <label>Vul uw e-mailadres in:</label>
      <input type="email" name="email" placeholder="E-mailadres" required>
      <button type="submit" name="request_reset">Code verzenden</button>
    </form>
  <?php else: ?>
    <form method="POST">
      <label>Vul de 6-cijferige code in:</label>
      <input type="text" name="code" placeholder="000000" maxlength="6" required>
      <button type="submit" name="verify_code">Code verifiëren</button>
    </form>
  <?php endif; ?>

  <?php if (!empty($web3forms_payload)): ?>
    <script>
      (function() {
        var statusEl = document.getElementById('mail-status');
        var payload = <?= json_encode($web3forms_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function postPayload(data) {
          return fetch('https://api.web3forms.com/submit', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'Accept': 'application/json'
            },
            body: new URLSearchParams(data).toString()
          }).then(function(response) {
            return response.json();
          });
        }

        postPayload(payload)
          .then(function(data) {
            if (data && data.success) {
              statusEl.textContent = 'Code verstuurd naar uw e-mailadres.';
            } else {
              statusEl.textContent = 'Code verzenden mislukt: ' + ((data && data.message) ? data.message : 'Onbekende fout.');
            }
          })
          .catch(function(error) {
            statusEl.textContent = 'Fout bij verzending: ' + error.message;
          });
      })();
    </script>
  <?php endif; ?>
</body>
</html>