<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "www.ilovefreegle.org";


require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/user/Story.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$s = new Story($dbhr, $dbhm);
$mysqltime = date("Y-m-d", max(strtotime("06-sep-2016"), strtotime("Midnight 90 days ago")));
foreach ([21623, 21354, 21589, 21406, 21423, 21306] as $groupid) {
    $count = $s->askForStories($mysqltime, NULL, 3, 5, $groupid);
}
#$count = $s->askForStories($mysqltime, NULL, Story::ASK_OUTCOME_THRESHOLD, Story::ASK_OFFER_THRESHOLD, NULL);
error_log("Sent $count requests");

unlockScript($lockh);