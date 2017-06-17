<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$opts = getopt('e:a:r:i:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_email.php (-e <email to find> or -i <user id>) (-a <email to add> -r <email to remove>\n";
} else {
    $uid = presdef('i', $opts, NULL);
    $find = presdef('e', $opts, NULL);
    $add = presdef('a', $opts, NULL);
    $remove = presdef('r', $opts, NULL);
    $u = User::get($dbhr, $dbhm);
    $uid = $uid ? $uid : $u->findByEmail($find);

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
    } else {
        error_log("Couldn't find user for $find");
    }
}
