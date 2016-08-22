<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$locs = $dbhr->preQuery("SELECT locations.* FROM locations INNER JOIN locations_excluded ON locations.areaid = locations_excluded.locationid WHERE gridid IS NOT NULL;");

$l = new Location($dbhr, $dbhm);

foreach ($locs as $loc) {
    error_log("#{$loc['id']} {$loc['name']}");
    $l->setParents($loc['id'], $loc['gridid']);
}
