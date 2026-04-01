<?php
session_start();
require_once '../src/authentication.php';
require_once '../src/appointments.php';
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
      <div class="container">
        <div class="appointment">
          <p class="section-title">Dashboard</p>

          <?php if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
            $appointment = new Appointments();
            $appointments = $appointment->getAppointmentsByEmail($_SESSION['email'] ?? '');

            foreach ($appointments as $appointment) {
              $appointmentId = (int) ($appointment['id'] ?? 0);
              $begintime = $appointment['begintime'] ?? null;
              $endtime = $appointment['endtime'] ?? null;
              $type = trim((string) ($appointment['type'] ?? ''));
              $status = trim((string) ($appointment['status'] ?? ''));

              $hasSchedule = !empty($begintime) && !empty($endtime);
              $startTimestamp = $hasSchedule ? strtotime((string) $begintime) : false;
              $canCancel = $hasSchedule
                && $startTimestamp !== false
                && ($startTimestamp - time()) > (24 * 60 * 60)
                && strtolower($status) === 'gepland';

              if ($hasSchedule && strtotime($endtime) < time() || strtolower($status) === 'geannuleerd') {
                continue; // Alleen afspraken in verleden overslaan als ze ingepland waren
              }

              $createdAt = $appointment['created_at'] ?? null;
              $dateDisplay = 'Wordt ingepland';
              $timeDisplay = 'Wordt ingepland';

              if ($hasSchedule) {
                $dateDisplay = date('d-m-Y', strtotime($begintime));
                $timeDisplay = date('H:i', strtotime($begintime)) . ' uur';
              }
          ?>

              <div class="appointment-card">
                <div class="left">
                  <p class="next-appointment-text">
                    <i class="bi bi-calendar-event"></i> Volgende afspraken
                  </p>

                  <div class="type-name"><?php echo htmlspecialchars(($appointment['type'] ?? 'Onbekend') . ' - ' . ($appointment['brand'] ?? 'Onbekend merk')); ?></div>

                  <div class="date-time">
                    <i class="bi bi-calendar"></i>
                    <div class="date"><?php echo htmlspecialchars($dateDisplay); ?></div>

                    <i class="bi bi-clock-fill"></i>
                    <div class="time"><?php echo htmlspecialchars($timeDisplay); ?></div>
                  </div>

                  <div class="status-detail">
                    <div class="status">
                      <p class="status-text">Status</p>
                      <div class="status"><?php echo htmlspecialchars($appointment['status'] ?? 'Onbekend'); ?></div>
                    </div>

                    <div class="detail">
                      <?php if ($canCancel && $appointmentId > 0): ?>
                        <button
                          type="button"
                          class="detail-button cancel-appointment-button"
                          data-appointment-id="<?php echo htmlspecialchars((string) $appointmentId); ?>"
                        >
                          Afspraak annuleren
                        </button>
                      <?php else: ?>
                        <span class="detail-button" style="opacity:.6;cursor:not-allowed;">Niet annuleerbaar</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="right">
                  <img src="<?php echo htmlspecialchars($appointment['photo_path'] ?? 'assets/images/default-image.png'); ?>" alt="" class="appointment-image">
                </div>
              </div>
          <?php
            }
          }
          ?>

        </div>

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
      </div>
    </section>

  </main>
</body>

<script>
  document.querySelectorAll('.cancel-appointment-button').forEach(function(button) {
    button.addEventListener('click', function() {
      var id = this.getAttribute('data-appointment-id');
      if (!id) {
        alert('Kon afspraak niet bepalen.');
        return;
      }

      if (!confirm('Weet je zeker dat je deze afspraak wilt annuleren?')) {
        return;
      }

      fetch('appointment_cancel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
      })
        .then(function(response) {
          return response.json().then(function(data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function(result) {
          if (result.ok && result.data && result.data.success) {
            alert('Afspraak succesvol geannuleerd.');
            window.location.reload();
            return;
          }

          var message = (result.data && result.data.message) ? result.data.message : 'Onbekende fout.';
          alert(message);
        })
        .catch(function() {
          alert('Netwerkfout bij annuleren.');
        });
    });
  });
</script>

</html>