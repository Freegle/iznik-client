<?php

session_start();

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

$groupid = presdef('graffitigroup', $_SESSION, 0);
$type = presdef('graffititype', $_SESSION, 'Page');
$url = presdef('url', $_REQUEST, NULL);;

error_log("Type $type group $groupid url $url");

$fb = new Facebook\Facebook([
    'app_id' => FBGRAFFITIAPP_ID,
    'app_secret' => FBGRAFFITIAPP_SECRET
]);

if ($url) {
    $accessToken = $_SESSION['fbaccesstoken'];
    error_log("Acess token $accessToken");

    if (preg_match('/.*\/groups\/(\d*)/', $url, $matches)) {
        $fbgroupid = $matches[1];

        $ret = $fb->get("/$fbgroupid", $accessToken);
        $group = $ret->getGraphGroup();

        if ($group->getPrivacy() == 'OPEN') {
            $g = new GroupFacebook($dbhr, $dbhm);
            $g->add($groupid, (string)$accessToken, $group->getName(), $group->getId(), GroupFacebook::TYPE_GROUP);
            print("Added OK.  You can close this page.");
        }  else {
            print ("<br />This isn't an Open group on Facebook - you can't use it.");
        }
    } else {
        print "<br />Couldn't find the ID of that Facebook group.  Please check the URL - it needs to have a number in it.<br />";
    }
} else {
    print "No URL provided";
}