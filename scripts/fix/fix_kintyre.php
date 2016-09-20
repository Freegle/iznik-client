<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');

$pcs = [
    "PA21"=>"Tighnabruaich",
    "PA22"=>"Colintraive",
    "PA23"=>"Dunoon",
    "PA24"=>"Cairndow",
    "PA25"=>"Cairndow",
    "PA26"=>"Cairndow",
    "PA27"=>"Cairndow",
    "PA28"=>"Campbeltown",
    "PA29"=>"Tarbert",
    "PA30"=>"Lochgilphead",
    "PA31"=>"Lochgilphead",
    "PA32"=>"Inveraray",
    "PA33"=>"Dalmally",
    "PA34"=>"Obam",
    "PA37"=>"Oban",
    "PA80"=>"Oban",
    "PA35"=>"Taynuilt",
    "PA36"=>"Bridge of Orchy",
    "PA38"=>"Appin"
];

$g = new geoPHP();
$l = new Location($dbhr, $dbhm);

foreach ($pcs as $pc => $area) {
    $points = [];
    $locs = $dbhr->preQuery("SELECT ASTEXT(geometry) AS geom, lat, lng FROM locations WHERE name LIKE '$pc %' AND type = 'Postcode';");
    foreach ($locs as $loc) {
        $pstr = "POINT({$loc['lng']} {$loc['lat']})";
        $points[] = $g::load($pstr);
    }

    $mp = new MultiPoint($points);
    $hull = $mp->convexHull();
    $geom = $hull->asText();

    $id = $l->create(NULL, $area, 'Polygon', $geom);
    error_log("$area => $geom");
}
