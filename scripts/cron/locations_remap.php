<?php

# Remap recently changed locations as a fallback.
define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$lockh = lockScript(basename(__FILE__));

$l = new Location($dbhr, $dbhm);

$mysqltime = date("Y-m-d", strtotime("Midnight 3 days ago"));
$sql = "SELECT locations.*, ASText(CASE WHEN ourgeometry IS NOT NULL THEN ourgeometry ELSE geometry END) AS geom FROM  `locations` WHERE  `type` =  'Polygon' AND  `timestamp` >= ? ORDER BY name ASC;";
$locs = $dbhr->preQuery($sql, [ $mysqltime ]);

$count = 0;
$pcids = [];

foreach ($locs as $loc) {
    $count++;
    $pcs = $dbhr->preQuery("SELECT id, gridid FROM locations WHERE areaid = ? AND type = 'Postcode' AND LOCATE(' ', name) > 0;", [ $loc['id'] ]);
    foreach ($pcs as $pc) {
        $pcids[$pc['id']] = $pc['gridid'];
    }

    error_log("Check {$loc['name']} ($count / " . count($locs) . ") postcodes so far " . count($pcids));
}

$count = 0;
foreach ($pcids as $id => $gridid) {
    $count++;
    error_log("$id ($count / " . count($pcids) . ")");
    $l->setParents($id);
}

unlockScript($lockh);