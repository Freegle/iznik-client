<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

error_log("Start at " . date("Y-m-d H:i:s"));

# We only do this for Facebook groups.  For pages, this gets us marked as spam.
$groups = $dbhr->preQuery("SELECT groups_facebook.uid, groups.nameshort FROM groups INNER JOIN groups_facebook ON groups.id = groups_facebook.groupid WHERE groups.type = 'Freegle' AND groups_facebook.type = 'Group' AND publish = 1 AND valid = 1 ORDER BY LOWER(nameshort) ASC;");
foreach ($groups as $group) {
    $f = new GroupFacebook($dbhr, $dbhm, $group['uid']);
    $count = $f->postMessages();
    error_log("{$group['nameshort']} $count");
}

error_log("Finish at " . date("Y-m-d H:i:s"));

unlockScript($lockh);