<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <form method="post">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Password">
    <button type="submit" name="login">Login</button>
  </form>

  <a href="registreer.php">Registreren</a>


  <?php
    session_start();
    if (isset($_POST['login'])) 
      {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
          echo "Vul alle velden in.";
        } else {
          require_once '../src/authentication.php';
          $auth = new Authenticate();
          if ($auth->login($username, $password)) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
          } else {
            echo "Invalid username or password.";
          }
        }
      }
  ?>
</body>
</html>