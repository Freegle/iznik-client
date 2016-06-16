<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/mail/Digest.php');

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm digest.php -i <interval>\n";
} else {
    $interval = $opts['i'];
}

$lockh = lockScript(basename(__FILE__) . "-$interval");

# We only send digests for Freegle groups.
$groups = $dbhr->preQuery("SELECT id, nameshort FROM groups WHERE `type` = 'Freegle';");
$d = new Digest($dbhr, $dbhm);

foreach ($groups as $group) {
    $d->send($group['id'], $interval);
}

unlockScript($lockh);