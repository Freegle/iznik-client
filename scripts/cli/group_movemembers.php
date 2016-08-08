<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('f:t:');

if (count($opts) < 2) {
    echo "Usage: hhvm group_movemembers.php -f <shortname of source group> -t <short name of destination group>\n";
} else {
    $from = $opts['f'];
    $to = $opts['t'];
    $g = new Group($dbhr, $dbhm);

    $srcid = $g->findByShortName($from);
    $dstid = $g->findByShortName($to);
    $moved = 0;
    $alreadys = 0;

    if ($srcid && $dstid) {
        $membs = $dbhr->preQuery("SELECT * FROM memberships WHERE groupid = ?;", [ $srcid ]);
        foreach ($membs as $memb) {
            $membs2 = $dbhr->preQuery("SELECT * FROM memberships WHERE groupid = ? AND userid = ?;", [$dstid, $memb['userid']]);
            $already = count($membs2) > 0;
            if (!$already) {
                $dbhm->preQuery("UPDATE memberships SET groupid = ? WHERE groupid = ? AND userid = ?;", [$dstid, $srcid, $memb['userid']]);
                $moved++;
            } else {
                $alreadys++;
            }
        }
    } else {
        error_log("Groups not found");
    }

    error_log("Moved $moved, already member $alreadys");
    $dbhm->preExec("DELETE FROM memberships WHERE groupid = ?;", [ $srcid ]);
}
