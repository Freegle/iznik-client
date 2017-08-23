<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$yms = $dbhr->preQuery("SELECT DISTINCT membershipid, COUNT(*) AS count FROM memberships_yahoo GROUP BY membershipid HAVING count > 1;");
$count = count($yms);

foreach ($yms as $ym) {
    $mins = $dbhr->preQuery("SELECT MIN(added) AS min FROM memberships_yahoo WHERE membershipid = ?;", [ $ym['membershipid'] ], FALSE);
    foreach ($mins as $min) {
        $dbhm->preExec("UPDATE memberships SET added = ? WHERE id = ? AND added > ?;", [
            $min['min'],
            $ym['membershipid'],
            $min['min']
        ], FALSE);

        $dbhm->preExec("UPDATE users SET added = ? WHERE id = (SELECT userid FROM memberships WHERE id = ?) AND added > ?;", [
            $min['min'],
            $ym['membershipid'],
            $min['min']
        ], FALSE);
    }

   $at++;

    if ($at % 1000 == 0) {
        error_log("...$at / $count");
    }
}