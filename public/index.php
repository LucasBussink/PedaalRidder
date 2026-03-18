<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
  <header>
    <div class="container">
      <a href="index.php" class="logo">
        <img src="assets/images/logo-transparent.png" alt="website logo">
        <span class="orange">De</span>
        <span class="blue">Pedaal</span>
        <span class="orange">Ridder</span>
      </a>

      <nav>
        <?php
        if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
          echo '<a href="logout.php" class="logout-button">Uitloggen</a>';
          echo '<a href="afspraken_inplannen.php" class="appointments-button">Afspraken Inplannen</a>';
        } else {
          echo '<a href="login.php" class="login-button">Inloggen</a>';
          echo '<a href="registreer.php" class="register-button">Registreren</a>';
        }
        ?>
      </nav>
    </div>
  </header>

  <main>
    <div class="hero">
      <div class="container">
        <div class="left">
          <h2 class="hero-title">Uw fiets snel en vakkundig gerepareerd</h2>

          <p class="hero-text">Professionele fietsservice voor iedere ridder op <br> de weg. Van e-bikes tot racefietsen, wij zorgen <br> dat u veilig en soepel blijft trappen</p>

          <a href="reparatie_aanmelden.php" class="appointment-button">Maak afspraak</a>
        </div>

        <div class="right">
          <img src="assets/images/De man hemzelf.png" alt="De pedaalridder">
        </div>
      </div>
    </div>
  </main>
</body>

</html>
