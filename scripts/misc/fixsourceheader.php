<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

do {
    $sql = "SELECT * FROM messages_approved ORDER BY arrival DESC LIMIT $at, " . ($at + 100) . ";";
    error_log($sql);

    $msgs = $dbhr->query($sql);
    $i = new IncomingMessage($dbhr, $dbhm);
    $found = false;

    foreach ($msgs as $msg) {
        $found = true;
        $i->parse(IncomingMessage::YAHOO_APPROVED, $msg['fromaddr'], $msg['envelopeto'], $msg['message']);
        if ($i->getSourceheader() != $msg['sourceheader']) {
            error_log("{$msg['id']} Change from {$msg['sourceheader']} to " . $i->getSourceheader());
            $sql = "UPDATE messages_approved SET sourceheader = ? WHERE id = ?;";
            $dbhm->preExec($sql,
                [
                    $i->getSourceheader(),
                    $msg['id']
                ]);
        } else {
            #error_log("{$msg['id']} {$msg['fromaddr']} remains {$msg['sourceheader']}");
        }
    }

    $at += 100;
} while ($found);