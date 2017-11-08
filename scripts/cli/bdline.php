<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Authority.php');
require_once(IZNIK_BASE . '/lib/php-shapefile/src/ShapeFileAutoloader.php');
require_once(IZNIK_BASE . '/lib/phpcoord.php');

\ShapeFile\ShapeFileAutoloader::register();

// Import classes
use \ShapeFile\ShapeFile;
use \ShapeFile\ShapeFileException;

$a = new Authority($dbhr, $dbhm);
$opts = getopt('f:');

$fn = presdef('f', $opts, NULL);

if ($fn) {
    $sf = new ShapeFile($fn);

    while ($record = $sf->getRecord(ShapeFile::GEOMETRY_WKT)) {
        error_log($record['dbf']['NAME']);

        # Get the WKT, which at this point is in easting/northing format.
        $wkt = $record['shp'];
        $new = '';
        $last = 0;

        while (preg_match('/([0-9\.\s]+)/', $wkt, $matches, PREG_OFFSET_CAPTURE, $last)) {
            $en = trim($matches[1][0]);
            $off = $matches[1][1];

            if (strlen($en)) {
                #error_log("Found match $en at $off");
                $p = strpos($en, ' ');
                if ($p) {
                    $easting = substr($en, 0, $p);
                    $northing = substr($en, $p + 1);
                    $os = new OSRef($easting, $northing);
                    $latlng = $os->toLatLng();
                    $new .= substr($wkt, $last, $off - $last) . $latlng->lng . ' ' . $latlng->lat;
                    #error_log("$easting $northing => {$latlng->lng} {$latlng->lat}");
                } else {
                    error_log("Failed to parse " . var_export($matches, TRUE));
                    file_put_contents('/tmp/a', $wkt);
                    exit(1);
                }
            } else {
                $new .= substr($wkt, $last, $off - $last);
            }

            #error_log("Move on " . strlen($matches[1][0]) . " from {$matches[1][0]}");
            $last = $matches[1][1] + strlen($matches[1][0]);
        }

        $new .= substr($wkt, $last);

        # Some polygons aren't closed; add the start point as an end point.
        $p = strpos($new, '((');
        $q = strpos($new, ',');
        $start = substr($new, $p + 2, $q - $p -2);
        $p = substr($new, 0, strlen($new) - 2) . ", $start))";

        try {
            $a->create(
                $record['dbf']['NAME'],
                $record['dbf']['AREA_CODE'],
                $new
            );
        } catch (Exception $e) {
            file_put_contents('/tmp/a', $new);
            file_put_contents('/tmp/b', $wkt);
            error_log("...failed");
        }
    }
}
