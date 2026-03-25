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

require_once '../src/planning.php';
require_once '../src/appointments.php';

$planning = new Planning();
$appointments = new Appointments();
$unplannedCount = count($appointments->getUnplannedAppointments());

$selectedDateRaw = $_GET['datum'] ?? date('Y-m-d');
$selectedTimestamp = strtotime($selectedDateRaw);

if ($selectedTimestamp === false) {
    $selectedTimestamp = strtotime(date('Y-m-d'));
}

$selectedMonday = date('Y-m-d', strtotime('monday this week', $selectedTimestamp));
$days = $planning->getWorkweekDays($selectedMonday);

$previousWeekDate = date('Y-m-d', strtotime($selectedMonday . ' -7 days'));
$nextWeekDate = date('Y-m-d', strtotime($selectedMonday . ' +7 days'));

$currentMonday = date('Y-m-d', strtotime('monday this week'));
$isCurrentWeek = $selectedMonday === $currentMonday;

$weekNumber = date('W', strtotime($selectedMonday));

if ($unplannedCount === 0) {
    $unplannedMessage = 'Er zijn geen afspraken om in te plannen.';
} elseif ($unplannedCount === 1) {
    $unplannedMessage = 'Er moet nog 1 afspraak ingepland worden.';
} else {
    $unplannedMessage = 'Er moeten nog ' . $unplannedCount . ' afspraken ingepland worden.';
}

function formatDutchDay($date)
{
    $dutchDayNames = [
        1 => 'Maandag',
        2 => 'Dinsdag',
        3 => 'Woensdag',
        4 => 'Donderdag',
        5 => 'Vrijdag',
        6 => 'Zaterdag',
        7 => 'Zondag',
    ];

    $dayNumber = (int) date('N', strtotime($date));
    return $dutchDayNames[$dayNumber] . ' ' . date('d-m-y', strtotime($date));
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | PedaalRidder</title>
  <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/adminAgenda.css">
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
        <section class="admin-agenda">
            <div class="week-nav">
                <a href="?datum=<?php echo urlencode($previousWeekDate); ?>">&larr; Vorige week</a>
                <span class="week-info">
                    Week <?php echo htmlspecialchars($weekNumber); ?>
                    <?php if ($isCurrentWeek): ?>
                        - huidige week
                    <?php endif; ?>
                </span>
                <a href="?datum=<?php echo urlencode($nextWeekDate); ?>">Volgende week &rarr;</a>
            </div>

            <div class="agenda-table-wrapper">
                <table class="agenda-table">
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <th><?php echo htmlspecialchars(formatDutchDay($day)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <td>
                                <?php
                                $repairs = $planning->getRepairsForDay($day);

                                if (count($repairs) > 0) {
                                    foreach ($repairs as $repair) {
                                        $startTime = date('H:i', strtotime($repair['begintime']));
                                        $endTime = date('H:i', strtotime($repair['endtime']));

                                        $repairDetails = [
                                            'id' => $repair['id'] ?? '',
                                            'datum' => date('d-m-Y', strtotime($repair['begintime'])),
                                            'tijd' => $startTime . ' - ' . $endTime,
                                            'klant' => $repair['customer_name'] ?? 'Onbekend',
                                            'type' => $repair['type'] ?? '',
                                            'status' => $repair['status'] ?? '',
                                            'merk' => $repair['brand'] ?? '',
                                            'model' => $repair['model'] ?? '',
                                            'omschrijving' => $repair['description'] ?? '',
                                            'foto' => $repair['photo_path'] ?? '',
                                        ];

                                        $repairJson = htmlspecialchars(json_encode($repairDetails), ENT_QUOTES, 'UTF-8');

                                        echo "<button type='button' class='appointment-card' data-appointment='" . $repairJson . "'>";
                                        echo '<b>' . htmlspecialchars($startTime . ' - ' . $endTime) . '</b><br>';
                                        echo 'Soort: ' . htmlspecialchars($repair['type']) . '<br>';
                                        echo 'Status: ' . htmlspecialchars($repair['status']);
                                        echo '</button>';
                                    }
                                } else {
                                    echo '<span class="agenda-empty">Geen reparaties</span>';
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </div>

            <div class="unplanned-bar<?php echo $unplannedCount === 0 ? ' is-empty' : ''; ?>">
                <?php echo htmlspecialchars($unplannedMessage); ?>
            </div>
            
        </section>

<div id="appointment-modal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <button type="button" class="modal-close" id="modal-close" aria-label="Sluiten">&times;</button>
        <h2 id="modal-title">Afspraak details</h2>
        <div class="modal-grid">
            <strong>ID</strong><span id="modal-id"></span>
            <strong>Datum</strong><span id="modal-date"></span>
            <strong>Tijd</strong><span id="modal-time"></span>
            <strong>Klant</strong><span id="modal-customer"></span>
            <strong>Type</strong><span id="modal-type"></span>
            <strong>Status</strong><span id="modal-status"></span>
            <strong>Merk</strong><span id="modal-brand"></span>
            <strong>Model</strong><span id="modal-model"></span>
            <strong>Omschrijving</strong><span id="modal-description"></span>
            <strong>Foto</strong><span id="modal-photo"></span>
        </div>
    </div>
</div>
  </main>
</body>

</html>
<script>
    const modal = document.getElementById('appointment-modal');
    const closeModalButton = document.getElementById('modal-close');

    function setModalText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value && value.trim() !== '' ? value : '-';
        }
    }

    function openAppointmentModal(appointment) {
        setModalText('modal-id', String(appointment.id || ''));
        setModalText('modal-date', String(appointment.datum || ''));
        setModalText('modal-time', String(appointment.tijd || ''));
        setModalText('modal-customer', String(appointment.klant || ''));
        setModalText('modal-type', String(appointment.type || ''));
        setModalText('modal-status', String(appointment.status || ''));
        setModalText('modal-brand', String(appointment.merk || ''));
        setModalText('modal-model', String(appointment.model || ''));
        setModalText('modal-description', String(appointment.omschrijving || ''));

        const photoElement = document.getElementById('modal-photo');
        photoElement.innerHTML = '';

        if (appointment.foto && appointment.foto.trim() !== '') {
            const image = document.createElement('img');
            image.src = appointment.foto;
            image.alt = 'Reparatiefoto';
            image.className = 'modal-photo';
            photoElement.appendChild(image);
        } else {
            photoElement.textContent = '-';
        }

        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeAppointmentModal() {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.appointment-card').forEach((card) => {
        card.addEventListener('click', () => {
            const payload = card.getAttribute('data-appointment');
            if (!payload) {
                return;
            }

            try {
                const appointment = JSON.parse(payload);
                openAppointmentModal(appointment);
            } catch (error) {
                console.error('Kon afspraakdetails niet openen', error);
            }
        });
    });

    closeModalButton.addEventListener('click', closeAppointmentModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeAppointmentModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeAppointmentModal();
        }
    });
</script>