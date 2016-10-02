<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$msgs = $dbhr->preQuery("SELECT * FROM  `chat_messages` WHERE  `date` >  '2016-09-29 15:20' AND chatid = 374342;");

foreach ($msgs as $msg) {
    $previous = $dbhr->preQuery("SELECT * FROM chat_messages WHERE chatid = ? AND id < ? AND date < '2016-09-29 15:20' ORDER BY date DESC LIMIT 1;", [
        $msg['chatid'],
        $msg['id']
    ]);

    $previd = count($previous) > 0 ? $previous[0]['id'] : NULL;

    error_log("Chat #{$msg['chatid']} #{$msg['id']} previous $previd");
    $dbhm->preExec("UPDATE chat_roster SET lastmsgemailed = ?, lastemailed = NULL WHERE lastmsgemailed = ?;", [ $previd, $msg['id'] ]);
}