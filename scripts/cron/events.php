<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/mail/EventDigest.php');

$opts = getopt('i:m:v:');

if (count($opts) < 1) {
    echo "Usage: hhvm event.php (-m mod -v val)\n";
} else {
    $interval = $opts['i'];
    $mod = presdef('m', $opts, 1);
    $val = presdef('v', $opts, 0);

    $lockh = lockScript(basename(__FILE__) . "-m$mod-v$val");

    error_log("Start events for groupid % $mod = $val at " . date("Y-m-d H:i:s"));
    $start = time();
    $total = 0;

    # We only send events for Freegle groups.
    $groups = $dbhr->preQuery("SELECT id, nameshort FROM groups WHERE `type` = 'Freegle' AND onhere = 1 AND MOD(id, ?) = ? AND publish ORDER BY LOWER(nameshort) ASC;", [$mod, $val]);
    $e = new EventDigest($dbhr, $dbhm);

    foreach ($groups as $group) {
        $total += $e->send($group['id']);
    }

    $duration = time() - $start;

    error_log("Finish events at " . date("Y-m-d H:i:s") . ", sent $total mails in $duration seconds");

    unlockScript($lockh);
}