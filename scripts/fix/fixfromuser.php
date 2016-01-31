<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

do {
    $sql = "SELECT * FROM messages WHERE fromuser IS NULL ORDER BY arrival DESC LIMIT $at, " . ($at + 100) . ";";
    error_log($sql);

    $msgs = $dbhr->query($sql);
    $i = new Message($dbhr, $dbhm);
    $found = false;

    foreach ($msgs as $msg) {
        $found = true;
        $i->parse(Message::YAHOO_APPROVED, $msg['fromaddr'], $msg['envelopeto'], $msg['message']);
        error_log("{$msg['id']} Change fromuser to " . $i->getFromuser());
        $sql = "UPDATE messages SET fromuser = ? WHERE id = ?;";
        $dbhm->preExec($sql,
            [
                $i->getFromuser(),
                $msg['id']
            ]);
    }

    $at += 100;
} while ($found);