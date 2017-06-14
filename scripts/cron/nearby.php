<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/user/Nearby.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$n = new Nearby($dbhr, $dbhm);
$count = 0;

$groups = $dbhr->preQuery("SELECT groups.id, groups.nameshort FROM groups WHERE groups.type = 'Freegle' AND publish = 1 ORDER BY LOWER(nameshort) ASC;");
foreach ($groups as $group) {
    error_log($group['nameshort']);
    $thiscount = $n->messages($group['id']);
    error_log("...$thiscount");
    $count += $thiscount;
}

error_log("Sent $count");

unlockScript($lockh);