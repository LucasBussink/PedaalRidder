<?php
include_once '../src/user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <h1>Registreren</h1>
  <form method="post">
    <input type="text" name="username" placeholder="Username">
    <input type="email" name="email" placeholder="Email">
    <input type="password" name="password" placeholder="Password">
    <button type="submit" name="register">Registreer</button>
  </form>

  <a href="login.php">Terug naar login</a>

  <?php
    //als er op de register knop is gedrukt
    if (isset($_POST['register'])) {
      $username = $_POST['username'];
      $email = $_POST['email'];
      $password = $_POST['password'];

      if (empty($username) || empty($email) || empty($password)) {
        echo "Vul alle velden in.";
      } else {
        $user = new User();
        $user->addUser($username, $password, $email);
      }
    }
  ?>
</body>
</html>