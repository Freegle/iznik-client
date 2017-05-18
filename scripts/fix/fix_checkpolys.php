<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');


$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 AND nameshort NOT LIKE '%playground%' AND nameshort NOT LIKE '%test%' ORDER BY LOWER(nameshort);");
$found = 0;
$notfound = 0;
$maxoverlap = 0;
$maxpoly = NULL;
$polyname = 'polyofficial';
$polyname = 'COALESCE(poly, polyofficial)';
$threshold = 0.05;

foreach ($groups as $group) {
    try {
        $poly = $group['poly'] ? $group['poly'] : $group['polyofficial'];
        #$poly = $group['polyofficial'];
        $sql = "SELECT id, nameshort, (ST_Area(ST_Intersection(GeomFromText($polyname), GeomFromText(?)))/LEAST(ST_Area(GeomFromText($polyname)), ST_Area(GeomFromText(?)))) AS area, AsText(ST_Intersection(GeomFromText($polyname), GeomFromText(?))) AS overlap FROM groups WHERE id != ? AND ST_Overlaps(GeomFromText($polyname), GeomFromText(?)) AND publish = 1;";
        $overlaps = $dbhr->preQuery($sql,
            [
                $poly,
                $poly,
                $poly,
                $group['id'],
                $poly
            ]);

        foreach ($overlaps as $overlap) {
            if ($overlap['area'] > $threshold)
            {
                error_log("#{$group['id']}, {$group['nameshort']}, overlaps, #{$overlap['id']}, {$overlap['nameshort']}, with, {$overlap['area']}");
                if ($overlap['area'] > $maxoverlap && $overlap['id'] > $group['id']) {
                    $maxoverlap = $overlap['area'];
                    $maxpoly = $overlap['overlap'];
                }
            }

            break;
        }
    } catch (Exception $e) {
        #error_log("Couldn't check {$group['id']}" . $e->getMessage());
    }
}

error_log("Max overlap $maxoverlap poly $maxpoly");