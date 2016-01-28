
<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;
$attr = "yahooid";

$sql = "SELECT $attr, COUNT(*) c FROM users WHERE $attr IS NOT NULL GROUP BY $attr HAVING c > 1;";
$yids = $dbhr->preQuery($sql);
$u = new User($dbhr, $dbhm);

foreach ($yids as $yid) {
    $users = $dbhr->preQuery("SELECT id FROM users WHERE $attr = ?;", [ $yid[$attr]]);
    foreach ($users as $user) {
        if ($user['id'] != $users[0]['id']) {
            error_log("Merge {$users[0]['id']} <= {$user['id']}");
            $u->merge($users[0]['id'], $user['id']);
        }
    }
}
