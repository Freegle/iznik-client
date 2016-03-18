<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$l = new Location($dbhr, $dbhm);

$locs = $dbhm->query("SELECT id,name FROM locations_ni WHERE canon IS NULL;");

$count = 0;

foreach ($locs as $loc) {
    $locs2 = $dbhm->query("SELECT id, name FROM locations WHERE id = {$loc['id']};");
    foreach ($locs2 as $loc2) {
        $can = $l->canon($loc2['name']);
        $dbhm->preExec("UPDATE locations SET canon = ? WHERE id = ?;", [ $can, $loc2['id']]);
        $count++;

        if ($count % 1000 == 0) {
            error_log("$count...");
        }
    }

}
