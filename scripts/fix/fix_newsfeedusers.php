<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$dbhm->preExec("START TRANSACTION;");

do {
    $users = $dbhm->preQuery("SELECT userid, COUNT( * ) AS count
FROM  `newsfeed_users` 
GROUP BY userid
HAVING count >1;");

    $total = count($users);
    $count = 0;

    foreach ($users as $user) {
        $maxes = $dbhm->preQuery("SELECT MAX(id) AS max FROM newsfeed_users WHERE userid = ?;", [
            $user['userid']
        ], FALSE);

        foreach ($maxes as $max) {
            error_log("{$user['userid']} max {$max['max']}");
            $dbhm->preQuery("DELETE FROM newsfeed_users WHERE userid = ? AND id < ?;", [
                $user['userid'],
                $max['max']
            ], FALSE);
        }

        $count++;

        if ($count % 1000 === 0) {
            error_log("...$count / $total");
        }
    }
    error_log("Completed $total");
} while ($total > 0);

$dbhm->preExec("ALTER TABLE newsfeed_users DROP INDEX userid, ADD UNIQUE KEY userid (userid);");
$dbhm->preExec("COMMIT;");
