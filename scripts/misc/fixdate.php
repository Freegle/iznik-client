<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/IncomingMessage.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;
$table = "messages_approved";

do {
    error_log("At $at");
    $sql = "SELECT * FROM $table WHERE date IS NULL LIMIT 100;";

    $msgs = $dbhr->query($sql);
    $i = new IncomingMessage($dbhr, $dbhm);
    $found = false;

    foreach ($msgs as $msg) {
        $found = true;
        $i->parse(IncomingMessage::YAHOO_APPROVED, $msg['fromaddr'], $msg['envelopeto'], $msg['message']);
        $date = gmdate("Y-m-d H:i:s", strtotime($i->date));
        $sql = "UPDATE $table SET date = ? WHERE id = ?;";
        $dbhm->preExec($sql,
            [
                $date,
                $msg['id']
            ]);

        $at++;
    }
} while ($found);