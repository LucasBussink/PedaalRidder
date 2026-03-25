<?php
session_start();
$errorMessage = '';

if (isset($_POST['login'])) {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($email) || empty($password)) {
    $errorMessage = "Vul alle velden in.";
  } else {
    require_once '../src/authentication.php';
    $auth = new Authenticate();

    if ($auth->login($email, $password)) {
      $_SESSION['login'] = true;
      $_SESSION['email'] = $email;
      $_SESSION['is_admin'] = $auth->checkIsAdmin($email);

      if ($_SESSION['is_admin'] === true) {
        header("Location: adminIndex.php");
        exit();
      }

      header("Location: index.php");
      exit();
    } else {
      $errorMessage = "Invalid email or password.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
  <main>
    <div class="top">
      <div class="background-image">
        <div class="logo">
          <div class="image-container">
            <img src="assets/images/logo-transparent.png" alt="Dikke logo ouleh">
          </div>

          <p class="name">De PedaalRidder</p>
        </div>
      </div>
    </div>

    <div class="bottom">
      <h2 class="title">Login</h2>

      <p class="description">Welkom terug ridder, vul a.u.b. uw gegevens in.</p>

      <form method="POST">
        <div class="field">
          <label>E-mailadres</label>
          <div class="input">
            <i class="bi bi-person-fill"></i>
            <input type="email" placeholder="Voer uw e-mail in">
          </div>
        </div>

        <div class="field">
          <div class="label-row">
            <label>Wachtwoord</label>
            <a href="#">Wachtwoord vergeten?</a>
          </div>

          <div class="input">
            <i class="bi bi-lock-fill"></i>
            <input type="password" placeholder="Voer uw wachtwoord in">
          </div>
        </div>

        <button type="submit" name="login">
          Inloggen
          <i class="bi bi-box-arrow-in-right"></i>
        </button>

        <?php if (!empty($errorMessage)) { ?>
          <p><?= htmlspecialchars($errorMessage) ?></p>
        <?php } ?>
      </form>

      <hr>

      <p class="register">
        Heeft u nog geen account?
        <a href="registreer.php">Account aanmaken</a>
      </p>
    </div>
  </main>

</body>

</html>
