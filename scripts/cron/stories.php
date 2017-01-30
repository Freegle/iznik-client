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
$groups = $dbhr->preQuery("SELECT groups.id, groups.nameshort FROM groups WHERE groups.type = 'Freegle' AND publish = 1 ORDER BY LOWER(nameshort) ASC;");
$count = 0;
foreach ($groups as $group) {
    error_log("Check group {$group['nameshort']}");
    $count += $s->askForStories($mysqltime, NULL, 3, 5, $group['id']);
}
error_log("Sent $count requests");

unlockScript($lockh);