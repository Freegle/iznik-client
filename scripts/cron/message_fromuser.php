<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$u = User::get($dbhr, $dbhm);

$messages = $dbhr->preQuery("SELECT id, fromuser, fromaddr FROM messages");

$count = 0;

foreach ($messages as $message) {
    $uid = $u->findByEmail($message['fromaddr']);
    if ($uid != $message['fromuser']) {
        $dbhm->preExec("UPDATE messages SET fromuser = ? WHERE id = ?;", [ $uid, $message['id']]);
    }

    $count++;

    if ($count % 100 == 0) {
        error_log("...$count");
    }
}
