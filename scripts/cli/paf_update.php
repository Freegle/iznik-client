<?php

# This is for updates to the PAF file.  Don't run it for the initial load - it's too slow.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/PAF.php');

$opts = getopt('i:o:');

if (count($opts) < 1) {
    echo "Usage: hhvm paf_update.php -i input PAF CSV filename\n";
} else {
    $p = new PAF($dbhr, $dbhm);
    $p->update($opts['i']);
}
