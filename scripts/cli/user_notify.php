<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');

$opts = getopt('i:t:u:');

if (count($opts) < 2) {
    echo "Usage: hhvm user_notify.php -i <user ID> -t <type> (-u url)\n";
} else {
    $id = $opts['i'];
    $type = $opts['t'];
    $url = $opts['u'];
    $n = new Notifications($dbhr, $dbhm);
    $n->add(NULL, $id, $type, NULL, $url);
}
