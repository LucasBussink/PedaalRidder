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
                $query = "SELECT
                                        a.*,
                                        c.name AS customer_name
                                    FROM appointments AS a
                                    LEFT JOIN customers AS c ON a.customer_id = c.id
                                    WHERE DATE(a.begintime) = ?
                                    ORDER BY a.begintime";
        return parent::voerQueryUit($query, [$day]);
    }
}