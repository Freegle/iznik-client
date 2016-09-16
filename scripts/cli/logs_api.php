<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

# Get average CPU cost per call.
$logs = $dbhr->preQuery("SELECT response FROM logs_api;");

$cpu = [];
$count = [];

foreach ($logs as $log) {
    $rsp = json_decode($log['response'], TRUE);

    if (!array_key_exists($rsp['call'], $cpu)) {
        $cpu[$rsp['call']] = 0;
        $count[$rsp['call']] = 0;
    }

    $cpu[$rsp['call']] += $rsp['cpucost'];
    $count[$rsp['call']]++;
}

error_log("\n\nTotals:\n\n");

arsort($cpu);

foreach ($cpu as $call => $total) {
    error_log("$call = $total (count {$count[$call]})");
}
error_log("\n\nAverages:\n\n");
$avg = [];

foreach ($cpu as $call => $total) {
    $avg[$call] = $total / $count[$call];
}

arsort($avg);

foreach ($avg as $call => $cost) {
    error_log("$call = $cost (count {$count[$call]})");
}