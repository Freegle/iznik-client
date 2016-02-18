
<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$sql = "SELECT * FROM users WHERE yahooid LIKE '%@%';";
$users = $dbhr->preQuery($sql);

$u = new User($dbhr, $dbhm);
$total = count($users);
error_log("Got $total");

foreach ($users as $user) {
    # See if the u
    $p = strpos($user['yahooid'], '@');
    $yahooid = substr($user['yahooid'], 0, $p);
    #error_log("{$user['yahooid']} => $yahooid");

    $others = $dbhr->preQuery("SELECT id FROM users WHERE yahooid = ?;", [ $yahooid ]);
    foreach ($others as $other) {
        #error_log("Merge {$other['id']} => {$user['id']} ");
        $u->merge($user['id'], $other['id'], "Fix: Yahoo ID with @, merge {$user['yahooid']} => $yahooid");
    }

    $dbhm->preExec("UPDATE users SET yahooid = ? WHERE id = ?;", [ $yahooid, $user['id']]);

    $at++;
    if ($at % 1000 == 0) {
        error_log("...$at/$total");
    }
}
