<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');

$lockh = lockScript(basename(__FILE__));

$n = new Newsfeed($dbhr, $dbhm);
$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND onhere = 0 AND publish = 1 AND nameshort NOT LIKE '%playground%' ORDER BY RAND();");
foreach ($groups as $group) {
    $g = new Group($dbhr, $dbhm, $group['id']);

    if ($g->getSetting('newsfeed', TRUE)) {
        error_log("{$group['nameshort']}");

        $membs = $dbhr->preQuery("SELECT DISTINCT userid FROM memberships WHERE groupid = ? AND collection = ?;", [
            $group['id'],
            MembershipCollection::APPROVED
        ]);

        $count = 0;
        foreach ($membs as $memb) {
            $count += $n->digest($memb['userid']);
        }

        if ($count) {
            error_log("{$group['nameshort']} sent $count");
        }
    } else {
        error_log("{$group['nameshort']} skipped");
    }
}

unlockScript($lockh);