<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$opts = getopt('n:');

if (count($opts) < 1) {
    echo "Usage: hhvm voucher_create.php -n <number of vouchers to create>\n";
} else {
    $num = $opts['n'];
    $g = new Group($dbhr, $dbhm);

    echo "Create $num Vouchers:\n\n";

    for ($i = 0; $i < $num; $i++) {
        echo $g->createVoucher() . "\n";
    }
}
