<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$locs = $dbhr->preQuery("SELECT * FROM locations WHERE gridid IS NULL;");
$total = count($locs);
$count = 0;

foreach ($locs as $loc) {
    $sql = "SELECT locations_grids.id AS gridid FROM `locations` INNER JOIN locations_grids ON locations.id = ? AND MBRIntersects(locations_ni.geometry, locations_grids.box) LIMIT 1;";
    $grids = $dbhr->preQuery($sql, [ $loc['id'] ]);
    foreach ($grids as $grid) {
        $gridid = $grid['gridid'];
        $sql = "UPDATE locations_ni SET gridid = ?, maxdimension = GetMaxDimension(geometry) WHERE id = ?;";
        $dbhm->preExec($sql, [ $grid['gridid'], $loc['id'] ]);
    }

    $count++;

    if ($count % 1000 == 0) {
        error_log("...$count/$total");
    }
}
