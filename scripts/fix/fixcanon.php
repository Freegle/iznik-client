<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$l = new Location($dbhr, $dbhm);

$locs = $dbhm->query("SELECT id,name FROM locations WHERE canon IS NULL;");

$count = 0;

foreach ($locs as $loc) {
    $can = $l->canon($loc['name']);
    $dbhm->preExec("UPDATE locations SET canon = ? WHERE id = ?;", [ $can, $loc['id']]);
    $count++;

    if ($count % 1000 == 0) {
        error_log("$count...");
    }
}
