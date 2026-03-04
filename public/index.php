<?php
session_start();

include_once '../src/customer.php';

$cust = new Customer();

$customer = $cust->Get(1);
print_r($customer);



if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
  echo "Welkom, " . $_SESSION['username'] . "!";

  echo "<a href='logout.php'>Log uit</a>";
  
} else {
  echo "Je bent niet ingelogd.";

  echo "<a href='login.php'>Log in</a>";
  echo "<a href='registreer.php'>Registreer</a>";
}



?>







