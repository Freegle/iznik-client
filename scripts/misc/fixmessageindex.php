<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$s = new Search($dbhr, $dbhm, 'messages_index', 'msgid', 'arrival', 'words');

do {
    $found = FALSE;
    $sql = "SELECT id, subject, date FROM messages ORDER BY arrival DESC LIMIT $at, " . ($at + 1000) . ";";

    $msgs = $dbhr->query($sql);

    foreach ($msgs as $msg) {
        $s->add($msg['id'], $msg['subject'], strtotime($msg['date']));
        $found = TRUE;
    }

    $at += 1000;
    error_log("...$at");
} while ($found);