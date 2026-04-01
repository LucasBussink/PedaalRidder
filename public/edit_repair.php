<?php
session_start();
require_once '../src/authentication.php';
require_once '../src/repairs.php';

$auth = new Authenticate();
$repairs = new Repairs();

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

$repairId = (int) ($_GET['id'] ?? 0);

if ($repairId <= 0) {
  header('Location: basic_repairs.php');
  exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $minutes = (int) ($_POST['minutes'] ?? 0);

  if ($name === '' || $minutes <= 0) {
    $error = 'Naam en duur (minuten) zijn verplicht.';
  } else {
    $repairs->updateBasicRepair($repairId, $name, $description, $minutes);
    header('Location: basic_repairs.php');
    exit();
  }
}

$repair = $repairs->getBasicRepairById($repairId);

if (!$repair) {
  header('Location: basic_repairs.php');
  exit();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Repair Wijzigen</title>
  <link rel="stylesheet" href="assets/css/editRepair.css" />
</head>
<body>
  <h1>Standaard reparatie wijzigen</h1>
  
  <?php if ($error !== ''): ?>
    <p class="msg-err"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  
  <form method="POST">
    <label>Naam:</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($repair['name'] ?? ''); ?>" required>
    
    <label>Beschrijving:</label>
    <textarea name="description"><?php echo htmlspecialchars($repair['description'] ?? ''); ?></textarea>
    
    <label>Duur (minuten):</label>
    <input type="number" name="minutes" min="0" step="5" value="<?php echo htmlspecialchars((string) ($repair['minutes'] ?? '')); ?>" required>

    <div class="actions">
      <button type="submit">Opslaan</button>
      
    </div>
    <br>
    <div class="actions">
    <a href="basic_repairs.php">Terug</a>
    </div>
  </form>
</body>
</html>