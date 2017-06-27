<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$vols = $dbhr->preQuery("SELECT * FROM volunteering WHERE id NOT IN (SELECT volunteeringid FROM volunteering_groups)");
$users = [];

foreach ($vols as $vol) {
    error_log("{$vol['id']} {$vol['title']}");
    $already = $dbhr->preQuery("SELECT * FROM volunteering WHERE userid = ? AND pending = 0 AND title = ?;", [
        $vol['userid'],
        $vol['title']
    ]);

    if (count($already) > 0) {
        error_log("...already approved");
    } else if (array_key_exists($vol['userid'], $users)) {
        error_log("...wait");
    } else {
        $u = new User($dbhr, $dbhm, $vol['userid']);
        $membs = $u->getMemberships();

        if (count($membs) == 0) {
            error_log("No groups");
            $dbhm->preExec("DELETE FROM volunteering WHERE id = ?;", [
                $vol['id']
            ]);
        } else if (count($membs) > 0) {
            error_log("Too many groups");
        } else {
            $g = new Group($dbhr, $dbhm, $membs[0]['groupid']);
            error_log("...for " . $g->getPrivate('nameshort'));
        }
    }
}