<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');

function canon($name) {
    foreach (['freegle', 'reuse', 'recycle', 'freeworld_recycling', 'greencycle', 'uk', '-', ' and ', '_', ' ', '&'] as $word) {
        $name = str_ireplace($word, '', $name);
    }
    return(strtolower($name));
}

$kml = simplexml_load_file(GATKML);
$g = new Group($dbhr, $dbhm);

if ($kml) {
    $kgroups = $kml->Document->Folder->children();

    $groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 AND nameshort NOT LIKE '%playground%' AND nameshort NOT LIKE '%test%';");
    $found = 0;
    $notfound = 0;

    foreach ($groups as $group) {
        $name = $group['nameshort'];

        $match = 0;
        $exact = FALSE;
        $poly = NULL;

        foreach ($kgroups as $kgroup) {
            $kname = $kgroup->name;
            $p = strpos($kname, ':');
            $kname = $p ? trim(substr($kname, $p + 1)) : $kname;

            #error_log("Compare " . canon($name) . ", " . canon($kname) . " = " . strcmp(canon($name), canon($kname)));

            if (strcmp(canon($name), canon($kname)) === 0) {
                $poly = $kgroup->Polygon;
                $exact = TRUE;
            } else if (stripos(canon($kname), canon($name)) !== FALSE) {
                $poly = $kgroup->Polygon;
                $match++;
            }
        }

        if (!$exact && $match != 1) {
            error_log("...$name not found " . ($group['poly'] ? ", got guess" : ", no guess"));
            $notfound++;
        } else {
            error_log("...found");
            $found++;

            if ($poly) {
                $geom = geoPHP::load($poly->asXML(), 'kml');
                $wkt = $geom->out('wkt');

                $dbhm->preExec("UPDATE groups SET polyapproved = 1, poly = ? WHERE id = ?;", [$wkt, $group['id']]);
            }
        }
    }

    error_log("Found $found not $notfound");
} else {
    error_log("Failed to get KML");
}