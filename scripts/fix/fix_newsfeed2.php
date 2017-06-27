<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');

$n = new Newsfeed($dbhr, $dbhm);

$feeds = $dbhr->preQuery("SELECT id, userid, Y(position) AS lat, X(position) AS lng FROM newsfeed WHERE AsText(position) = 'POINT(-2.5209 53.945)';");

foreach ($feeds as $feed) {
    $u = new User($dbhr, $dbhm, $feed['userid']);
    list($lat, $lng) = $u->getLatLng();

    if($lat != $feed['lat'] || $lng != $feed['lng']) {
        error_log("{$feed['id']} {$feed['lat']}, {$feed['lng']} => $lat, $lng");
        $dbhm->preExec("UPDATE newsfeed SET position = GeomFromText('POINT($lng $lat)') WHERE id = ?;", [
            $feed['id']
        ]);
    }
}