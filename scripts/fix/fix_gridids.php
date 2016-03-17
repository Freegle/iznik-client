<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$locs = $dbhr->preQuery("SELECT * FROM locations_ni WHERE gridid IS NULL;");

foreach ($locs as $loc) {
    $sql = "SELECT locations_grids.id AS gridid FROM `locations_ni` INNER JOIN locations_grids ON locations_ni.id = ? AND MBRIntersects(locations_ni.geometry, locations_grids.box) LIMIT 1;";
    $grids = $dbhr->preQuery($sql, [ $loc['id'] ]);
    foreach ($grids as $grid) {
        $gridid = $grid['gridid'];
        $sql = "UPDATE locations_ni SET gridid = ?, maxdimension = GetMaxDimension(geometry) WHERE id = ?;";
        $dbhm->preExec($sql, [ $grid['gridid'], $loc['id'] ]);
    }
}
