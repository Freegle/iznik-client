<?php

# Run on backup server to recover a user from a backup to the live system.  Use with astonishing levels of caution.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host=localhost;port=3309;dbname=iznik;charset=utf8";
$dbhback = new PDO($dsn, SQLUSER, SQLPASSWORD, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => FALSE
));

$count = 0;
$changed = 0;

error_log("Query");
$addrs = $dbhback->query("SELECT id, thoroughfaredescriptorid FROM paf_addresses");
error_log("Queried");

foreach ($addrs as $addr) {
    $lives = $dbhr->preQuery("SELECT id FROM paf_addresses WHERE id = ? AND thoroughfaredescriptorid != ?;", [
        $addr['id'],
        $addr['thoroughfaredescriptorid']
    ], FALSE);

    foreach ($lives as $live) {
        $dbhm->preExec("UPDATE paf_addresses SET thoroughfaredescriptorid = ? WHERE id = ?;", [
            $addr['thoroughfaredescriptorid'],
            $addr['id']
        ], FALSE);

        $changed++;
    }

    $count++;

    if ($count % 1000 === 0) {
        error_log("... $count / changed $changed");
    }
}
