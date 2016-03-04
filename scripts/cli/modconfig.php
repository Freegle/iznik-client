<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');

$opts = getopt('a:i:f:');

if (count($opts) < 2) {
    echo "Usage: hhvm modconfig.php -a (export|import) -i <id to export> -f <input or output file>\n";
} else {
    $a = $opts['a'];
    $i = presdef('i', $opts, NULL);
    $f = $opts['f'];
    $c = new ModConfig($dbhr, $dbhm);

    if ($a == 'export') {
        $c = new ModConfig($dbhr, $dbhm, $i);
        file_put_contents($f, $c->export());
    } else if ($a == 'import') {
        $c->import(file_get_contents($f));
    }
}
