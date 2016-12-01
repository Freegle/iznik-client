<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

$lockh = lockScript(basename(__FILE__));

# Bypass our usual DB class as we don't want the overhead nor to log.
$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$dbhm = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$start = date('Y-m-d', strtotime("4 hours ago"));

error_log("Event logs:");
$total = 0;
do {
    $count = $dbhm->exec("DELETE FROM logs_events WHERE `timestamp` < '$start' LIMIT 1000;");
    $total += $count;
    error_log("...$total");
} while ($count > 0);

unlockScript($lockh);