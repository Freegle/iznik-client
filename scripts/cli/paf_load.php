<?php

# This is for the initial load of the PAF file.  Don't run it for updates.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/PAF.php');

$opts = getopt('i:o:');

if (count($opts) < 1) {
    echo "Usage: hhvm paf_load.php -i input PAF CSV filename -o output data CSV file prefix\n";
} else {
    $p = new PAF($dbhr, $dbhm);
    $p->load($opts['i'], $opts['o']);
}
