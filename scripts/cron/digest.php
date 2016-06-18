<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/mail/Digest.php');

$opts = getopt('i:m:v:');

if (count($opts) < 1) {
    echo "Usage: hhvm digest.php -i <interval> (-m mod -v val)\n";
} else {
    $interval = $opts['i'];
    $mod = presdef('m', $opts, 1);
    $val = presdef('v', $opts, 0);
}

$lockh = lockScript(basename(__FILE__) . "-$interval-m$mod-v$val");

error_log("Start digest for $interval groupid % $mod = $val at " . date("Y-m-d H:i:s"));
$start = time();
$total = 0;

# We only send digests for Freegle groups.
$groups = $dbhr->preQuery("SELECT id, nameshort FROM groups WHERE `type` = 'Freegle' AND MOD(id, ?) = ? ORDER BY LOWER(nameshort) ASC;", [ $mod, $val ]);
$d = new Digest($dbhr, $dbhm);

foreach ($groups as $group) {
    $total += $d->send($group['id'], $interval);
}

$duration = time() - $start;

error_log("Finish digest for $interval at " . date("Y-m-d H:i:s") . ", sent $total mails in $duration seconds");


unlockScript($lockh);