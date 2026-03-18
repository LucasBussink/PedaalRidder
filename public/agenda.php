<?php

require_once '../src/planning.php';

$planning = new Planning();

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

<div style="margin-bottom:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <a href="?datum=<?php echo urlencode($previousWeekDate); ?>">&larr; Vorige week</a>
    <span>
        Week <?php echo htmlspecialchars($weekNumber); ?>
        <?php if ($isCurrentWeek): ?>
            - huidige week
        <?php endif; ?>
    </span>
    <a href="?datum=<?php echo urlencode($nextWeekDate); ?>">Volgende week &rarr;</a>
</div>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <?php foreach ($days as $day): ?>
            <th><?php echo htmlspecialchars(formatDutchDay($day)); ?></th>
        <?php endforeach; ?>
    </tr>
    <tr>
        <?php foreach ($days as $day): ?>
            <td valign="top">
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
                    echo 'Geen reparaties';
                }
                ?>
            </td>
        <?php endforeach; ?>
    </tr>
</table>

<style>
    .appointment-card {
        width: 100%;
        text-align: left;
        border: 1px solid black;
        margin: 5px 0;
        padding: 8px;
        background: #fff;
        cursor: pointer;
    }

    .appointment-card:hover {
        background: #f5f8ff;
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .modal-content {
        background: #fff;
        border-radius: 8px;
        width: min(560px, 100%);
        padding: 20px;
        position: relative;
    }

    .modal-close {
        position: absolute;
        right: 12px;
        top: 8px;
        border: none;
        background: transparent;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
    }

    .modal-grid {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 8px 12px;
    }

    .modal-grid strong {
        color: #333;
    }

    @media (max-width: 520px) {
        .modal-grid {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
</style>

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
            const link = document.createElement('a');
            link.href = appointment.foto;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'Bekijk foto';
            photoElement.appendChild(link);
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