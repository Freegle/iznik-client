<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');

$n = new Newsfeed($dbhr, $dbhm);

$mysqltime = date ("Y-m-d", strtotime("Midnight 7 days ago"));

$events = $dbhr->preQuery("SELECT * FROM communityevents WHERE added > '$mysqltime';");
foreach ($events as $event) {
    $exists = $dbhr->preQuery("SELECT * FROM newsfeed WHERE eventid = ?;", [ $event['id'] ]);
    if (count($exists) == 0) {
        $n->create(Newsfeed::TYPE_COMMUNITY_EVENT, $event['userid'], NULL, NULL, NULL, NULL, NULL, $event['id'], NULL, NULL);
        $n->setPrivate('timestamp', date ("Y-m-d H:i:s", strtotime($event['added'])));
    }
}

$volunteerings = $dbhr->preQuery("SELECT * FROM volunteering WHERE added > '$mysqltime';");
foreach ($volunteerings as $volunteering) {
    $exists = $dbhr->preQuery("SELECT * FROM newsfeed WHERE volunteeringid = ?;", [ $volunteering['id'] ]);
    if (count($exists) == 0) {
        $n->create(Newsfeed::TYPE_VOLUNTEER_OPPORTUNITY, $volunteering['userid'], NULL, NULL, NULL, NULL, NULL, NULL, $volunteering['id'], NULL);
        $n->setPrivate('timestamp', date ("Y-m-d H:i:s", strtotime($volunteering['added'])));
    }
}
