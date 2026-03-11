<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  header("Location: login.php");
  exit();
}

include_once '../src/appointments.php';
$appointments = new Appointments();
$unplannedAppointments = $appointments->getUnplannedAppointments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Afspraken Inplannen</title>
  <link rel="stylesheet" href="assets/css/afspraken_inplannen.css">
</head>
<body>
  <h1>Afspraken Inplannen</h1>
  <table border="1">
    <thead>
      <tr>
        <th>Klantnaam</th>
        <th>Fietsmerk</th>
        <th>Fietstype</th>
        <th>Probleemomschrijving</th>
        <th>Acties</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($unplannedAppointments as $appointment): ?>
        <tr>
          <td><?= htmlspecialchars($appointment['customer_name']) ?></td>
          <td><?= htmlspecialchars($appointment['brand']) ?></td>
          <td><?= htmlspecialchars($appointment['model']) ?></td>
          <td><?= htmlspecialchars($appointment['description']) ?></td>
          <td><a href="afspraak_inplannen.php?id=<?= $appointment['id'] ?>">Inplannen</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <a href="index.php">Terug naar home</a>