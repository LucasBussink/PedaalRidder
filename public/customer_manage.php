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

//alle gegevens van klanten ophalen
require_once '../src/customer.php';
$customers = new Customer();
$allCustomers = $customers->getAllCustomers();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Klantenbeheer</title>
  </head>
  <body>
    <h1>Klantenbeheer</h1>
    <table border="1">
      <tr>
        <th>ID</th>
        <th>Naam</th>
        <th>Email</th>
        <th>Telefoonnummer</th>
        <th>Aantal keer niet verschenen</th>
        
      </tr>
      <?php foreach ($allCustomers as $customer): ?>
      <tr>
        <td><?php echo htmlspecialchars($customer['id']); ?></td>
        <td><?php echo htmlspecialchars($customer['name']); ?></td>
        <td><?php echo htmlspecialchars($customer['email']); ?></td>
        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
        <td><?php echo htmlspecialchars($customer['no_show_count']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </body>
</html>