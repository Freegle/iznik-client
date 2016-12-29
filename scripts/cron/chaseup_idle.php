<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "www.ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/user/User.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE type = 'Freegle' AND publish = 1 ORDER BY RAND();");
$u = new User($dbhr, $dbhm);
$count = 0;

foreach ($groups as $group) {
    $count += $u->chaseUpIdle($group['id']);
}

error_log("Processed $count chaseups");

unlockScript($lockh);