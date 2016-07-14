<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/mail/EventDigest.php');

$opts = getopt('m:v:');

if (count($opts) < 1) {
    echo "Usage: hhvm event.php (-m mod -v val)\n";
} else {
    $mod = presdef('m', $opts, 1);
    $val = presdef('v', $opts, 0);

    $lockh = lockScript(basename(__FILE__) . "-m$mod-v$val");

    error_log("Start events for groupid % $mod = $val at " . date("Y-m-d H:i:s"));
    $start = time();
    $total = 0;

    $e = new EventDigest($dbhr, $dbhm, FALSE);

    # We only send events for Freegle groups.
    $sql = "SELECT id, nameshort FROM groups WHERE `type` = 'Freegle' AND onhere = 1 AND MOD(id, ?) = ? AND publish = 1 AND DATEDIFF(NOW(), lasteventsroundup) >= 7 ORDER BY LOWER(nameshort) ASC;";
    $groups = $dbhr->preQuery($sql, [$mod, $val]);

    foreach ($groups as $group) {
        error_log($group['nameshort']);
        $g = new Group($dbhr, $dbhm, $group['id']);
        $settings = $g->getPublic()['settings'];
        if ($settings['communityevents']) {
            $total += $e->send($group['id']);
        }
    }

    $duration = time() - $start;

    error_log("Finish events at " . date("Y-m-d H:i:s") . ", sent $total mails in $duration seconds");

    unlockScript($lockh);
}