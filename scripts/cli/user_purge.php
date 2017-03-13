<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_purge.php -i <user id>\n";
} else {
    $uid = $opts['i'];

    $u = new User($dbhr, $dbhm, $uid);
    if ($u->getId() == $uid) {
        $u->delete();
    }
}
