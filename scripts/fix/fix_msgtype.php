<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$msgs = $dbhr->preQuery("SELECT * FROM messages_groups WHERE msgtype IS NULL;");
$count = 0;

foreach ($msgs as $msg) {
    $msgs2 = $dbhr->preQuery("SELECT type FROM messages WHERE id = ?;", [ $msg['msgid'] ]);
    $dbhm->preExec("UPDATE messages_groups SET msgtype = ? WHERE msgid = ? AND groupid = ?;", [ $msgs2[0]['type'], $msg['msgid'], $msg['groupid']]);
    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count");
    }
}
