<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$m = new Message($dbhr, $dbhm);

$messages = $dbhr->preQuery("SELECT messages.*, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid WHERE messageid IS NULL");
$count = 0;

foreach ($messages as $message) {
    $m->parse($message['source'], $message['envelopefrom'], $message['envelopeto'], $message['message'], $message['groupid']);
    $msgid = $m->getMessageID();
    error_log("{$message['id']} $msgid");
    $others = $dbhr->preQuery("SELECT * FROM messages WHERE messageid = ?;", [$msgid]);

    $got = FALSE;

    foreach ($others as $other) {
        error_log("Already got msgid in {$other['id']}");
        $got = TRUE;
    }

    if ($got) {
        $dbhm->preExec("DELETE FROM messages WHERE id = ?;", [ $message['id']]);
    } else {
        $dbhm->preExec("UPDATE messages SET messageid = ? WHERE id = ?;", [ $msgid, $message['id']]);
    }

    $count++;

    if ($count % 100 == 0) {
        error_log("...$count");
    }
}
