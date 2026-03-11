<?php

require_once 'database.php';

class Planning extends Database
{
    public function getWorkweekDays($startWorkweek = null)
    {
        $monday = $startWorkweek ?? date('Y-m-d', strtotime('monday this week'));
        $days = [];

        for ($i = 0; $i < 5; $i++) {
            $days[] = date('Y-m-d', strtotime($monday . " +$i days"));
        }

        return $days;
    }

    public function getRepairsForDay($day)
    {
        $query = "SELECT begintime, endtime, type, status FROM appointments WHERE DATE(begintime) = ? ORDER BY begintime";
        return parent::voerQueryUit($query, [$day]);
    }
}