<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('f:t:r:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_merge.php -f <id of user to merge from> -t <id of user to merge into> -r <reason>\n";
} else {
    $from = $opts['f'];
    $to = $opts['t'];
    $reason = $opts['r'];
    $u = User::get($dbhr, $dbhm);
    $u->merge($to, $from, $reason);
}
