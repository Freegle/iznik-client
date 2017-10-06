<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');
require_once(IZNIK_BASE . '/lib/phpcoord.php');

$opts = getopt('i:a:');

$from = $opts['i'];
$auth = intval(presdef('a', $opts, 0));

$str = file_get_contents($from);

$g = new geoPHP();
$p = $g->load($str);

if (strpos($str, "MULTIPOLYGON") !== FALSE) {
    $hull = $p->convexHull();
    $points = $hull->getPoints();
} else {
    $points = $p->getPoints();
}

$str = "POLYGON((";
$first = TRUE;

foreach ($points as $point) {
    $e = $point->getX();
    $n = $point->getY();

    $os = new OSRef($e, $n);
    $latlng = $os->toLatLng();
    $str .= ($first ? '' : ', ') . $latlng->lng . " " . $latlng->lat;

    $first = FALSE;
}

$str .= "))";

if ($auth) {
    $dbhm->preExec("UPDATE authorities SET polygon = GeomFromText(?) WHERE id = ?;", [
        $auth,
        $str
    ]);
} else {
    $name = basename($from);
    $name = str_ireplace('Council', '', $name);
    $name = str_replace('-', ' ', $name);
    $name = str_replace('.txt', '', $name);
    $dbhm->preExec("INSERT INTO authorities (name, polygon) VALUES (?, GeomFromText(?));", [
        $name,
        $str
    ]);
}
