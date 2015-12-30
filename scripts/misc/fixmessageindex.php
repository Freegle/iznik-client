<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$s = new Search($dbhr, $dbhm, 'messages_index', 'msgid', 'arrival', 'words', 'groupid');

do {
    $found = FALSE;
    $sql = "SELECT messages.id, messages.subject, messages.date, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE id NOT IN (SELECT DISTINCT msgid FROM messages_index) ORDER BY messages.arrival DESC LIMIT $at, " . ($at + 1000) . ";";

    $msgs = $dbhr->query($sql);

    foreach ($msgs as $msg) {
        $s->add($msg['id'], $msg['subject'], strtotime($msg['date']), $msg['groupid']);
        $found = TRUE;
    }

    $at += 1000;
    error_log("...$at");
} while ($found);