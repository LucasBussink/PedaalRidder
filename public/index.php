<?php
session_start();

include_once '../src/customer.php';

$cust = new Customer();

$customer = $cust->Get(1);
echo "<pre>";
print_r($customer);
echo "</pre>";

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
  echo "Welkom, " . $_SESSION['username'] . "!";

  echo "<a href='logout.php'>Log uit</a>";
  echo "<a href='afspraken_inplannen.php'>Afspraken inplannen</a>";


} else {
  echo "Je bent niet ingelogd.";

  echo "<a href='login.php'>Log in</a>";
  echo "<a href='registreer.php'>Registreer</a>";
  echo "<a href='reparatie_aanmelden.php'>Reparatie aanmelden</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | De PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
  <header></header>
</body>
</html>
