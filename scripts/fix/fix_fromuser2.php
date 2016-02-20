<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$u = new User($dbhr, $dbhm);

$messages = $dbhr->preQuery("SELECT messages.id, fromuser, fromaddr FROM messages WHERE fromuser IS NOT NULL;");

$count = 0;

foreach ($messages as $message) {
    $id = $u->findByEmail($message['fromaddr']);

    if (!$id) {
        $u = new User($dbhr, $dbhm, $message['fromuser']);
        $u->addEmail($message['fromaddr'], 0);
    }

    $count++;

    if ($count % 1000) {
        error_log("...$count");
    }
}
