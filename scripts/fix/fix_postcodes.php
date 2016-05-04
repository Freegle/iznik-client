<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

#$dbhm->preExec("update locations set postcodeid = null where postcodeid is not null;");
#$dbhm->preExec("update locations set areaid = null where areaid is not null;");

$l = new Location($dbhr, $dbhm);

$sql = "SELECT id, gridid, name FROM locations WHERE LOCATE(' ', name) = 0 AND type = 'Postcode' ORDER BY name ASC;";
$pcs = $dbhr->preQuery($sql);
$count = 0;
$total = count($pcs);
foreach ($pcs as $pc) {
    $sql = "UPDATE locations SET postcodeid = {$pc['id']} WHERE name like '{$pc['name']} %' AND type = 'Postcode';";
    #error_log($sql);
    $dbhm->preExec($sql);

    $count++;

    if ($count % 100 == 0) {
        error_log("...$count / $total");
    }
}

exit(0);

$locs = $dbhr->preQuery("select id from locations where areaid not in (select id from locations);");
foreach ($locs as $loc) {
    $dbhm->preExec("UPDATE locations SET areaid = NULL WHERE id = ?;", [ $loc['id'] ]);
}

# We look at all full postcodes
#
# Edinburgh bounding box -3.417,55.867,-2.947,56.021
$count = 0;
$found = 0;

$sql = "SELECT id, gridid, name, type, lat, lng, geometry, AsText(geometry) AS geomtext FROM locations WHERE LOCATE(' ', name) > 0 AND type = 'Postcode' AND areaid IS NULL ORDER BY name ASC;";

$locs = $dbhr->preQuery($sql);
$total = count($locs);
foreach ($locs as $loc) {
    $id = $loc['id'];
    $gridid = $loc['gridid'];

    # Now we want to find the area.  We can speed up our query if we restrict the search to this grid square
    # and adjacent ones, but we need to work outwards until we find our location or it gets silly.
    $gridids = [ $gridid ];

    do {
        # Now find grids which touch our grid.  That avoids issues where our group is near the boundary of a grid square.
        $sql = "SELECT touches FROM locations_grids_touches WHERE gridid IN (" . implode(',', $gridids) . ");";
        $neighbours = $dbhr->preQuery($sql, [ $gridid ]);
        foreach ($neighbours as $neighbour) {
            $gridids[] = $neighbour['touches'];
        }

        $gridids = array_unique($gridids);

        # We choose the smallest non-postcode place location.  A place location is either one where the OSM data
        # says it's a place (osm_place) or the type of the location means it would work as one (not point,
        # basically).  We can't use MBRContains or MBRIntersects as some places are only present in OSM as points.
        $sql = "SELECT id, name, AsText(geometry) AS geomtext, haversine(lat, lng, ?, ?) AS dist FROM locations WHERE gridid IN (" . implode(',', $gridids) . ") AND osm_place = 1 ORDER BY dist ASC LIMIT 2;";
        #error_log("For $id $sql, {$loc['lat']}, {$loc['lng']}");
        $intersects = $dbhr->preQuery($sql, [
            $loc['lat'],
            $loc['lng']
        ]);
    } while (count($intersects) < 2 && count($gridids) < 10000);

    if (count($intersects) > 0) {
        #echo("...{$intersects[0]['name']} $top\n");
        # Quicker query if we omit AND id != $id and handle it here.
        $iid = $intersects[0]['id'] != $id ? $intersects[0]['id'] : $intersects[1]['id'];
        $sql = "UPDATE locations SET areaid = $iid WHERE id = $id;";
        #error_log($sql);
        $dbhm->background($sql);
        $found++;
    } else {
        error_log("Failed on {$loc['id']} {$loc['name']}");
    }

    $count++;

    if ($count % 1000 == 0) {
        error_log("...$found vs $count / $total");
    }
}

