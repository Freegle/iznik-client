<?php

define(SQLLOG, FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

# Use dbhm to bypass cache.
$l = new Location($dbhm, $dbhm);

$locs = $dbhm->query("SELECT id, name, gridid, areaid FROM locations WHERE type = 'Postcode' AND LOCATE(' ', name) > 0 ORDER BY name ASC;");

$count = 0;

foreach ($locs as $loc) {
    #echo "{$loc['id']} - {$loc['name']} #{$loc['areaid']} ";
    try {
        if ($loc['areaid']) {
            $areas = $dbhr->preQuery("SELECT id, name, ST_Dimension(CASE WHEN ourgeometry IS NOT NULL THEN ourgeometry ELSE geometry END) AS dim FROM locations WHERE id = ?;", [ $loc['areaid'] ]);
            foreach ($areas as $area) {
                #error_log("{$area['name']}");
                if ($area['dim'] != 2) {
                    error_log("#{$loc['id']} {$loc['name']} bad area {$area['id']} {$area['name']} dim {$area['dim']}");
                    $l->setParents($loc['id']);
                }
            }

        }
        $count++;

        if ($count % 1000 == 0) {
            error_log("$count..." . count($locs));
        }
    } catch (Exception $e) {}
}
