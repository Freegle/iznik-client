<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$counts = [];

$acts = $dbhr->preQuery("SELECT subtype, DATE_SUB(DATE(timestamp), INTERVAL DAYOFWEEK(timestamp)-1 DAY) AS date FROM logs WHERE timestamp >= '2017-01-01' AND type = 'Group' AND (subtype = 'Joined' OR (subtype = 'Left' AND byuser > 0)) AND (text IS NULL OR text NOT LIKE 'Sync of whole%') ORDER BY date ASC;");

foreach ($acts as $act) {
    if (!array_key_exists($act['date'], $counts)) {
        $counts[$act['date']] = [ 0, 0 ];
    }

    if ($act['subtype'] == 'Left') {
        $counts[$act['date']][1]++;
    } else if ($act['subtype'] == 'Joined') {
        $counts[$act['date']][0]++;
    }
}

foreach ($counts as $date => $count) {
    error_log("$date, {$count[0]}, {$count[1]}");
}