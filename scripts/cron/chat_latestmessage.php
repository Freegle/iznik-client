<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

# This is a fallback, so it's only run occasionally through cron.

$mysqltime = date("Y-m-d", strtotime("31 days ago"));
$chatids = $dbhr->preQuery("SELECT DISTINCT chatid FROM chat_messages WHERE date >= ?;", [ $mysqltime ]);

$total = count($chatids);
$count = 0;

foreach ($chatids as $chatid) {
    $dates = $dbhr->preQuery("SELECT MAX(date) AS maxdate FROM chat_messages WHERE chatid = ?;", [
        $chatid['chatid']
    ], FALSE);

    $dbhm->preExec("UPDATE chat_rooms SET latestmessage = ? WHERE id = ?;", [
        $dates[0]['maxdate'],
        $chatid['chatid']
    ], FALSE);

    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count / $total");
    }
}