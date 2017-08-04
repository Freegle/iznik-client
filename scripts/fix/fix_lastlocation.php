
<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$users = $dbhr->preQuery("SELECT id, lastlocation, settings FROM users WHERE settings IS NOT NULL;");

error_log(count($users) . " users");

$total = count($users);
$count = 0;

foreach ($users as $user) {
    $s = json_decode($user['settings'], TRUE);

    if (pres('mylocation', $s) && $s['mylocation']['id'] != $user['lastlocation']) {
        #error_log("{$user['id']} => {$s['mylocation']['id']}");

        $dbhm->preExec("UPDATE users SET lastlocation = ? WHERE id = ?;", [
            $s['mylocation']['id'],
            $user['id']
        ], FALSE);
    }

    $count ++;

    if ($count % 1000 == 0) {
        error_log("...$count / $total");
    }
}