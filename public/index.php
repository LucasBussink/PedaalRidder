<?php
session_start();
require_once '../src/authentication.php';
$auth = new Authenticate();

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
  if (!isset($_SESSION['is_admin'])) {
    $_SESSION['is_admin'] = $auth->checkIsAdmin($_SESSION['email'] ?? '');
  }

  if ($_SESSION['is_admin'] === true) {
    header('Location: adminIndex.php');
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/index.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
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
        } else {
          echo '<a href="login.php" class="login-button">Inloggen</a>';
          echo '<a href="registreer.php" class="register-button">Registreren</a>';
        }
        ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
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
    </section>



    <section class="dashboard">
      <div class="appointment">
        <p class="section-title">Dashboard</p>

        <?php if (isset($_SESSION['login']) && $_SESSION['login'] === true) { ?>
          <div class="appointment-card">
            <div class="left">
              <p class="next-appointment-text">
                <i class="bi bi-calendar-event"></i> Volgende afspraak
              </p>

              <div class="type-name">Grote beurt - Oranje gazelle</div>

              <div class="date-time">
                <i class="bi bi-calendar"></i>
                <div class="date">Woensdag 1 April</div>

                <i class="bi bi-clock-fill"></i>
                <div class="time">09:30 uur</div>
              </div>

              <div class="status-detail">
                <div class="status">
                  <p class="status-text">Status</p>
                  <div class="status">In behandeling</div>
                </div>

                <div class="detail">
                  <a href="" class="detail-button">Details bekijken</a>
                </div>
              </div>
            </div>

            <div class="right">
              <img src="" alt="" class="appointment-image">
            </div>
          </div>
        </div>
        <?php } ?>

      <div class="store-info">
        <p class="section-title">Winkel Info</p>

        <div class="info-card">
          <div class="location">
            <div class="icon">
              <i class="bi bi-geo-alt-fill"></i>
            </div>

            <div class="text">
              <div class="title">Locatie</div>
              <div class="info">J.F. Kennedylaan 49, Doetinchem</div>
            </div>
          </div>

          <div class="phone-number">
            <div class="icon">
              <i class="bi bi-telephone-fill"></i>
            </div>

            <div class="text">
              <div class="title">Telefoon</div>
              <div class="info">0314 353 500</div>
            </div>
          </div>

          <div class="mail">
            <div class="icon">
              <i class="bi bi-envelope-at-fill"></i>
            </div>

            <div class="text">
              <div class="title">Email</div>
              <div class="info">jan@pedaalridder.nl</div>
            </div>
          </div>

          <hr>

          <div class="opening-hours">
            <div class="days"></div>
          </div>
        </div>
      </div>
    </section>

  </main>
</body>

</html>
