<?php

# Set up gridids for locations already in the locations table; you might do this after importing a bunch of locations
# from a source such as OpenStreetMap (OSM).
require_once dirname(__FILE__) . '/../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$g = new Group($dbhr, $dbhm);
$gid = $g->findByShortName('FreeglePlayground');

if (!$gid) {
    # Not set up yet.
    error_log("Set up test environment");
    $gid = $g->create('FreeglePlayground', Group::GROUP_FREEGLE);
    $g->setPrivate('onyahoo', 0);
    $g->setPrivate('polyofficial', 'POLYGON((-3.1902622 55.9910847, -3.2472542 55.98263430000001, -3.2863922 55.9761038, -3.3159182 55.9522754, -3.3234712 55.9265089, -3.304932200000001 55.911888, -3.3742832 55.8880206, -3.361237200000001 55.8718436, -3.3282782 55.8729997, -3.2520602 55.8964911, -3.2177282 55.895336, -3.2060552 55.8903307, -3.1538702 55.88648049999999, -3.1305242 55.893411, -3.0989382 55.8972611, -3.0680392 55.9091938, -3.0584262 55.9215076, -3.0982522 55.928048, -3.1037452 55.9418938, -3.1236572 55.9649602, -3.168289199999999 55.9849393, -3.1902622 55.9910847))');
    $g->setPrivate('lat', 55.9533);
    $g->setPrivate('lng', 3.1883);

    $l = new Location($dbhr, $dbhm);
    $areaid = $l->create(NULL, 'Central', 'Polygon', 'POLYGON((-3.217620849609375 55.9565040997114,-3.151702880859375 55.9565040997114,-3.151702880859375 55.93304863776238,-3.217620849609375 55.93304863776238,-3.217620849609375 55.9565040997114))', 0);
    $pcid = $l->create(NULL, 'EH3 6SS', 'Postcode', 'POINT(55.957571 -3.205333)', 0);

    $u = new User($dbhr, $dbhm);
    $u->create('Test', 'User', 'Test User');
    $u->addEmail('test@test.com');
    $u->addLogin(User::LOGIN_NATIVE, NULL, 'freegle');
    $u->setMembershipAtt($gid, 'ourPostingStatus', Group::POSTING_DEFAULT);
} else {
    error_log("Test environment already set up.");
}

