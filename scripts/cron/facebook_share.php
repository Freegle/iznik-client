<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

error_log("Start at " . date("Y-m-d H:i:s"));

# Get the posts we want to offer mods to share.  This is a bit inefficient, but it's a background process.
$sharefroms = $dbhr->preQuery("SELECT DISTINCT sharefrom FROM groups_facebook;");

foreach ($sharefroms as $sharefrom) {
    # Find a token we can use to access this page.  Get them all, as some may be invalid.
    $tokens = $dbhr->preQuery("SELECT uid, token FROM groups_facebook WHERE sharefrom = ? AND valid = 1 AND token IS NOT NULL;", [
        $sharefrom['sharefrom']
    ]);

    foreach ($tokens as $token) {
        $f = new GroupFacebook($dbhr, $dbhm, $token['uid']);
        $f->getPostsToShare($sharefrom['sharefrom']);
    }
}

error_log("Finish at " . date("Y-m-d H:i:s"));

unlockScript($lockh);