<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/mail/Relevant.php');

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm user_relevant.php -i <user id>\n";
} else {
    $id = $opts['i'];
    $r = new Relevant($dbhr, $dbhm);
    $ints = $r->interestedIn($id);

    error_log("Found " . count($ints));

    foreach ($ints as $int) {
        error_log("  Type {$int['type']} Item {$int['item']} Because {$int['reason']}");
    }
}
