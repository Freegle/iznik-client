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

$opts = getopt('f:');

if (count($opts) != 1) {
    echo "Usage: hhvm doogle -f <CSV file>\n";
} else {
    $fn = presdef('f', $opts, NULL);

    if ($fn) {
        $l = new Location($dbhr, $dbhm);

        $fh = fopen($fn, 'r');

        if ($fh) {
            while (!feof($fh)) {
                # Format is:
                #
                # Postcode,In Use?,Latitude,Longitude,Easting,Northing,GridRef,County,District,Ward,DistrictCode,WardCode,Country,CountyCode,Constituency,Introduced,Terminated,Parish,NationalPark,Population,Households,Built up area,Built up sub-division,Lower layer super output area,Rural/urban,Region,Altitude,London zone,LSOA Code
                $fields = fgetcsv($fh);
                if ($fields[1] == 'Yes') {
                    $pc = $fields[0];
                    $lid = $l->findByName($pc);

                    if (!$lid) {
                        $lat = $fields[2];
                        $lng = $fields[3];
                        $lid = $l->create(NULL, $pc, 'Postcode', "POINT($lng $lat)", 0);

                        if ($lid) {
                            error_log("...added $pc $lat, $lng");
                        } else {
                            error_log("...failed to add $pc $lat, $lng");
                        }
                    }
                }
            }

            fclose($fh);
        }
    }
}
