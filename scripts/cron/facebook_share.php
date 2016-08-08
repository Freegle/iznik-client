<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

error_log("Start at " . date("Y-m-d H:i:s"));

$groups = $dbhr->preQuery("SELECT groups.id AS groupid, groups.*, groups_facebook.* FROM groups INNER JOIN groups_facebook ON groups.id = groups_facebook.groupid WHERE type = 'Freegle' AND publish = 1 AND valid = 1 AND groups.nameshort LIKE '%Edinburgh%' ORDER BY LOWER(nameshort) ASC;");
foreach ($groups as $group) {
    error_log("...#{$group['groupid']} {$group['nameshort']} token ");
    $f = new GroupFacebook($dbhr, $dbhm, $group['groupid']);
    $count = $f->shareFrom(TRUE);

    if ($count > 0) {
        error_log("{$group['nameshort']} $count");
    }
}

error_log("Finish at " . date("Y-m-d H:i:s"));

unlockScript($lockh);