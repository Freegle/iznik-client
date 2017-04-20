<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "www.ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

error_log("Start at " . date("Y-m-d H:i:s"));

$groups = $dbhr->preQuery("SELECT groups_facebook.uid, groups.nameshort FROM groups INNER JOIN groups_facebook ON groups.id = groups_facebook.groupid WHERE groups.type = 'Freegle' AND groups_facebook.type = 'Group' AND publish = 1 AND valid = 1 AND nameshort LIKE '%EldrickTest%' ORDER BY LOWER(nameshort) ASC;");
foreach ($groups as $group) {
    $f = new GroupFacebook($dbhr, $dbhm, $group['uid']);

    # Look for comments which we need to pull from Facebook into our platform.
    $count = $f->pollForChanges();
    error_log("{$group['nameshort']} replies from FB $count");

    # Look for chats which have had replies on the platform but not yet published a link on Facebook.
    $count = $f->postLinks();
    error_log("{$group['nameshort']} new links from platform $count");
}

error_log("Finish at " . date("Y-m-d H:i:s"));

unlockScript($lockh);