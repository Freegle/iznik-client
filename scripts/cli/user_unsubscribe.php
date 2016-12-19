<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('g:i:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_unsubscribe.php -i <user id> -g <group id>\n";
} else {
    $uid = $opts['i'];
    $gid = $opts['g'];

    $u = new User($dbhr, $dbhm, $uid);
    if ($u->getId() == $uid) {
        $u->removeMembership($gid);
    }
}
