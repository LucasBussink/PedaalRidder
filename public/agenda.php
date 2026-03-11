<?php

require_once '../src/planning.php';

$planning = new Planning();
$days = $planning->getWorkweekDays();

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