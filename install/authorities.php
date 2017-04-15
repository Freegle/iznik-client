<?php

require_once dirname(__FILE__) . '/../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');

$opts = getopt('f:');

if (count($opts) < 1) {
    echo "Usage: hhvm authorities.php -f <KML file>\n";
} else {
    $kml = simplexml_load_file($opts['f']);
    $g = Group::get($dbhr, $dbhm);

    if ($kml) {
        $folders = $kml->Document->children();

        foreach ($folders as $folder) {
            #error_log("Folder {$folder->getName()}");

            if ($folder->getName() == 'Folder') {
                $kgroups = $folder->children();

                foreach ($kgroups as $kgroup) {
                    $data = $kgroup->ExtendedData->SchemaData;
                    $name = NULL;

                    foreach ($data as $ent) {
                        $name = $ent->SimpleData[1];
                    }

                    if ($name) {
                        if ($kgroup->Polygon) {
                            error_log("...$name polygon");
                            $geom = geoPHP::load($kgroup->Polygon->asXML(), 'kml');
                            $wkt = $geom->out('wkt');
                            $dbhm->preExec("REPLACE INTO authorities (name, polygon) VALUES (?,GeomFromText(?));", [
                                $name,
                                $wkt
                            ]);
                        } else if ($kgroup->MultiGeometry) {
                            error_log("...$name multigeometry");
                            $geom = geoPHP::load($kgroup->MultiGeometry->asXML(), 'kml');
                            $wkt = $geom->out('wkt');
                            $dbhm->preExec("REPLACE INTO authorities (name, polygon) VALUES (?,GeomFromText(?));", [
                                $name,
                                $wkt
                            ]);
                        } else {
                            error_log("...$name skip");
                        }
                    }
                }
            }
        }
    } else {
        error_log("Failed to get KML");
    }
}