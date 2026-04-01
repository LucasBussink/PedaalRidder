<?php // een volledige pagina, zonder css, kijken of de admin ingelogd is. anders terug naar index.php. toon alle standaard reparaties met mogelijkheden om ze te bewerken of te verwijderen ook nieuwe toevoegen met name, description en minutes ?>

<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['is_admin'] !== true) {
  header("Location: index.php");
  exit();
}
require_once '../src/repairs.php';
$repairs = new Repairs();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['toevoegen'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $minutes = (int) ($_POST['minutes'] ?? 0);

    if ($name !== '') {
      $repairs->addBasicRepair($name, $description, $minutes);
      header("Location: basic_repairs.php");
      exit();
    }
  }

  $action = $_POST['action'] ?? '';
  $repairId = $_POST['repair_id'] ?? '';

  if ($action === 'delete') {
    $repairs->DeleteRepair($repairId);
    header("Location: basic_repairs.php");
    exit();
  } elseif ($action === 'edit') {
    header("Location: edit_repair.php?id=" . urlencode($repairId));
    exit();
  }
}

$basicRepairs = $repairs->GetAllTypeInfo();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Standaard Reparaties</title>
    <link rel="stylesheet" href="assets/css/basicRepairs.css" />
  </head>
  <body>
    <h1>Standaard Reparaties</h1>
    <table border="1">
      <tr>
        <th>ID</th>
        <th>Naam</th>
        <th>Beschrijving</th>
        <th>Duur (minuten)</th>
        <th>Acties</th>
      </tr>
      <?php foreach ($basicRepairs as $repair): ?>
      <tr>
        <td><?php echo htmlspecialchars($repair['id']); ?></td>
        <td><?php echo htmlspecialchars($repair['name']); ?></td>
        <td title="<?php echo htmlspecialchars($repair['description'] ?? ''); ?>" class="description-short">
          <?php echo htmlspecialchars($repair['description'] ?? ''); ?>
        </td>
        <td><?php echo htmlspecialchars($repair['minutes'] ?? ''); ?></td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="repair_id" value="<?php echo $repair['id']; ?>" />
            <button type="submit" name="action" value="edit">Bewerken</button>
            <button type="submit" name="action" value="delete" onclick="return confirm('Zeker?');">Verwijderen</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>

    <h2>Nieuwe Standaard Reparatie Toevoegen</h2>
    <form method="post">
      <input type="text" name="name" placeholder="Naam" required />
      <input type="text" name="description" placeholder="Beschrijving" />
      <input type="number" name="minutes" step="5" placeholder="Duur in minuten" />
      <button type="submit" name="toevoegen">Toevoegen</button>
    </form>

      <a href="index.php">Terug naar home</a>
  </body>
</html>
