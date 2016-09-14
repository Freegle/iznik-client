<?php

define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$count = 0;
$sql = "SELECT id FROM users;";
$users = $dbhr->preQuery($sql);
foreach ($users as $user) {
    $u = new User($dbhr, $dbhm, $user['id']);
    $emails = $u->getEmails();
    $ours = FALSE;
    foreach ($emails as $email) {
        if (ourDomain($email['email'])) {
            $ours = TRUE;
        }
    }

    if ($ours) {
        $u->setPrivate('publishconsent', 1);
    }

    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count / " . count($users));
    }
}
