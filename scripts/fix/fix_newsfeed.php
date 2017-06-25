<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');

$n = new Newsfeed($dbhr, $dbhm);

$mysqltime = date ("Y-m-d", strtotime("Midnight 7 days ago"));

$storys = $dbhr->preQuery("SELECT * FROM users_stories WHERE `date` > '$mysqltime'");
foreach ($storys as $story) {
    $exists = $dbhr->preQuery("SELECT * FROM newsfeed WHERE storyid = ?;", [ $story['id'] ]);
    if (count($exists) == 0) {
        if ($n->create(Newsfeed::TYPE_STORY, $story['userid'], NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $story['id'])) {
            $n->setPrivate('timestamp', date ("Y-m-d H:i:s", strtotime($story['date'])));
        }
    }
}

$publicitys = $dbhr->preQuery("SELECT * FROM groups_facebook_toshare WHERE `date` > '$mysqltime'");
foreach ($publicitys as $publicity) {
    $exists = $dbhr->preQuery("SELECT * FROM newsfeed WHERE publicityid = ?;", [ $publicity['id'] ]);
    if (count($exists) == 0) {
        if ($n->create(Newsfeed::TYPE_CENTRAL_PUBLICITY, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $publicity['id'])) {
            $n->setPrivate('timestamp', date ("Y-m-d H:i:s", strtotime($publicity['date'])));
        }
    }
}

$events = $dbhr->preQuery("SELECT * FROM communityevents WHERE added > '$mysqltime';");
foreach ($events as $event) {
    $exists = $dbhr->preQuery("SELECT * FROM newsfeed WHERE eventid = ?;", [ $event['id'] ]);
    if (count($exists) == 0) {
        if ($n->create(Newsfeed::TYPE_COMMUNITY_EVENT, $event['userid'], NULL, NULL, NULL, NULL, NULL, $event['id'], NULL, NULL)) {
            $n->setPrivate('timestamp', date ("Y-m-d H:i:s", strtotime($event['added'])));
        }
    }
}

$volunteerings = $dbhr->preQuery("SELECT * FROM volunteering WHERE added > '$mysqltime';");
foreach ($volunteerings as $volunteering) {
    $exists = $dbhr->preQuery("SELECT * FROM newsfeed WHERE volunteeringid = ?;", [ $volunteering['id'] ]);
    if (count($exists) == 0) {
        $u = User::get($dbhr, $dbhm, $volunteering['userid']);
        if ($u->getId()) {
            $n->create(Newsfeed::TYPE_VOLUNTEER_OPPORTUNITY, $volunteering['userid'], NULL, NULL, NULL, NULL, NULL, NULL, $volunteering['id'], NULL);
            $n->setPrivate('timestamp', date ("Y-m-d H:i:s", strtotime($volunteering['added'])));
        }
    }
}

