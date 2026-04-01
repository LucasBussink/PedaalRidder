<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  header('Location: login.php');
  exit();
}
require_once '../src/appointments.php';
$appointments = new Appointments();
$appointment = $appointments->getById($_GET['id'] ?? 0);

$dateTimeDisplay = 'Wordt ingepland';
if (!empty($appointment['begintime']) && !empty($appointment['endtime'])) {
  $startTs = strtotime((string) $appointment['begintime']);
  $endTs = strtotime((string) $appointment['endtime']);

  if ($startTs !== false && $endTs !== false) {
    $dateTimeDisplay = date('d-m-Y H:i', $startTs) . ' t/m ' . date('H:i', $endTs);
  }
}

$repairType = (string) ($appointment['type'] ?? 'Onbekend');
$repairDescription = trim((string) ($appointment['description'] ?? ''));
$photoPath = trim((string) ($appointment['photo_path'] ?? ''));
$isOverigType = strtolower($repairType) === 'overig';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Afspraak Details</title>
</head>

<body>
  <h1>Afspraak Details</h1>
  <?php if ($appointment): ?>
    <p><strong>Klant:</strong> <?php echo htmlspecialchars((string) ($appointment['customer_name'] ?? 'Onbekend')); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars((string) ($appointment['customer_email'] ?? '-')); ?></p>
    <p><strong>Telefoonnummer:</strong> <?php echo htmlspecialchars((string) ($appointment['customer_phone'] ?? '-')); ?></p>
    <p><strong>Datum en Tijd:</strong> <?php echo htmlspecialchars($dateTimeDisplay); ?></p>
    <p><strong>Reparaties:</strong></p>
    <ul>
      <li>
        <?php echo htmlspecialchars($repairType); ?>
        <?php if ($repairDescription !== ''): ?>
          - <?php echo htmlspecialchars($repairDescription); ?>
        <?php endif; ?>
      </li>
    </ul>
    
    <?php if ($isOverigType && $photoPath !== ''): ?>
      <p><strong>Afbeelding:</strong></p>
      <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Afspraak afbeelding">
    <?php endif; ?>
  <?php else: ?>
    <p>Afspraak niet gevonden.</p>
  <?php endif; ?>
</body>

</html>