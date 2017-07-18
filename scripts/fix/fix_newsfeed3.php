<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Volunteering.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');

$n = new Newsfeed($dbhr, $dbhm);

$feeds = $dbhr->preQuery("SELECT * FROM newsfeed WHERE position = GEOMFROMTEXT(  'POINT(-2.5209 53.945)' ) AND `type` IN ('VolunteerOpportunity', 'CommunityEvent');");

foreach ($feeds as $feed) {
    error_log("Check {$feed['id']}");
    if ($feed['type'] == 'VolunteerOpportunity') {
        $e = new Volunteering($dbhr, $dbhm, $feed['volunteeringid']);
    } else if ($feed['type'] == 'CommunityEvent') {
        $e = new CommunityEvent($dbhr, $dbhm, $feed['eventid']);
    }

    $atts = $e->getPublic();

    if (count($atts['groups'])) {
        $groupid = $atts['groups'][0]['id'];

        $g = new Group($dbhr, $dbhm, $groupid);
        $lat = $g->getPrivate('lat');
        $lng = $g->getPrivate('lng');
        error_log("{$feed['id']} => $lat, $lng");
        $dbhm->preExec("UPDATE newsfeed SET position = GeomFromText('POINT($lng $lat)') WHERE id = ?;", [
            $feed['id']
        ]);
    }
}