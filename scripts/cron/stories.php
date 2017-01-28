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
$count = $s->askForStories($mysqltime, NULL, 3, 21354);
$count = $s->askForStories($mysqltime, NULL, Story::ASK_THRESHOLD, NULL);
error_log("Sent $count requests");

unlockScript($lockh);