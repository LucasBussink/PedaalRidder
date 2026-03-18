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

                        echo "<div style='border:1px solid black; margin:5px; padding:5px;'>";
                        echo '<b>' . htmlspecialchars($startTime . ' - ' . $endTime) . '</b><br>';
                        echo 'Soort: ' . htmlspecialchars($repair['type']) . '<br>';
                        echo 'Status: ' . htmlspecialchars($repair['status']);
                        echo '</div>';
                    }
                } else {
                    echo 'Geen reparaties';
                }
                ?>
            </td>
        <?php endforeach; ?>
    </tr>
</table>