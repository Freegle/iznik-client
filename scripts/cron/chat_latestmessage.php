<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');

# This is a fallback, so it's only run occasionally through cron.

$mysqltime = date("Y-m-d", strtotime("31 days ago"));
$chatids = $dbhr->preQuery("SELECT DISTINCT chatid FROM chat_messages WHERE date >= ?;", [ $mysqltime ]);

$total = count($chatids);
$count = 0;

foreach ($chatids as $chatid) {
    $r = new ChatRoom($dbhr, $dbhm, $chatid['chatid']);
    $r->updateMessageCounts();

    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count / $total");
    }
}