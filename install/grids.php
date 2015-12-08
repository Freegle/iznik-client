<?php

# Set up gridids for locations already in the locations table; you might do this after importing a bunch of locations
# from a source such as OpenStreetMap (OSM).
require_once dirname(__FILE__) . '/../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

# Set up grids to cover a bounding box for the UK.
error_log("Ensure grids exist");
for ($swlat = 49; $swlat < 61; $swlat += 0.1) {
    for ($swlng = -14; $swlng < 5; $swlng += 0.1) {
        $nelat = $swlat + 0.1;
        $nelng = $swlng + 0.1;

        # Use lng, lat order for geometry because the OSM data uses that.
        $dbhm->preExec("INSERT IGNORE INTO locations_grids (swlat, swlng, nelat, nelng, box) VALUES (?, ?, ?, ?, GeomFromText('POLYGON(($swlng $swlat, $nelng $swlat, $nelng $nelat, $swlng $nelat, $swlng $swlat))'));",
            [
                $swlat,
                $swlng,
                $nelat,
                $nelng
            ]);
    }
}

error_log("Set up grids for locs");
Location::setGrids($dbhr, $dbhm);