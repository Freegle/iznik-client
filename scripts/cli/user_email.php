<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('e:a:r:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_email.php -e <email to find> (-a <email to add> -r <email to remove>\n";
} else {
    $find = $opts['e'];
    $add = $opts['a'];
    $remove = $opts['r'];
    $u = User::get($dbhr, $dbhm);
    $uid = $u->findByEmail($find);

    if ($uid) {
        error_log("Found user $uid");
        $u = User::get($dbhr, $dbhm, $uid);

        if ($add) {
            error_log("Added email $add");
            $u->addEmail($add);
        }

        if ($remove) {
            error_log("Removed email $remove");
            $u->removeEmail($remove);
        }
    }
}
