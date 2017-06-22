<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

# Make sure that the added date of a user reflects the earliest added date on their groups.
$users = $dbhr->preQuery("SELECT id FROM users;");
$total = count($users);
$count = 0;

foreach ($users as $user) {
    $dbhm->preExec("UPDATE users SET added = (SELECT MIN(added) FROM memberships WHERE userid = ?) WHERE id = ?;", [
        $user['id'],
        $user['id']
    ], FALSE);

    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count / $total");
    }
}