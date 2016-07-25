<?php

# We have a list of UK postcodes in the DB, among other locations.  UK postcodes change fairly frequently.
#
# 1. Go to https://www.ordnancesurvey.co.uk/opendatadownload/products.html
# 2. Download Code-Point Open, in ZIP form
# 3. Unzip it somewhere
# 4. Run this script to process it.
#
# It will add any new postcodes to the DB.
# TODO Removal?

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/lib/phpcoord.php');

$opts = getopt('d:');

if (count($opts) != 1) {
    echo "Usage: hhvm codepointopen -d <extracted zip folder>)\n";
} else {
    $fold = presdef('d', $opts, NULL);

    if ($fold) {
        $l = new Location($dbhr, $dbhm);

        foreach (glob("$fold/Data/CSV/*.*") as $file) {
            error_log("$file...");
            $fh = fopen($file, 'r');

            if ($fh) {
                while (!feof($fh)) {
                    # Format is:
                    #
                    # Postcode,Positional_quality_indicator,Eastings,Northings,Country_code,NHS_regional_HA_code,NHS_HA_code,Admin_county_code,Admin_district_code,Admin_ward_code
                    $fields = fgetcsv($fh);
                    $pc = $fields[0];

                    # Remove multiple spaces.
                    $pc = preg_replace('/\s+/', ' ', $pc);

                    # Might not be spaced correctly
                    if (strpos($pc, ' ') === FALSE) {
                        $pc = substr($pc, 0, 4) . ' ' . substr($pc, 4);
                    }

                    $lid = $l->findByName($pc);

                    if (!$lid) {
                        $easting = $fields[2];
                        $northing = $fields[3];
                        $os = new OSRef($easting, $northing);
                        $latlng = $os->toLatLng();
                        $lid = $l->create(NULL, $pc, 'Postcode', "POINT({$latlng->lng} $latlng->lat)", 0);

                        if ($lid) {
                            error_log("...added $pc {$latlng->lat}, {$latlng->lng}");
                        } else {
                            error_log("...failed to add $pc {$latlng->lat}, {$latlng->lng}");
                        }
                    }
                }

                fclose($fh);
            }
        }
    }
}
