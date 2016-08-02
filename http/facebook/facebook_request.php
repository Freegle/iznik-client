<?php

session_start();

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

$groupid = intval(presdef('groupid', $_REQUEST, 0));

$fb = new Facebook\Facebook([
    'app_id' => FBGRAFFITIAPP_ID,
    'app_secret' => FBGRAFFITIAPP_SECRET
]);

$helper = $fb->getRedirectLoginHelper();

$permissions = [
    'manage_pages',
    'publish_pages'
];

$_SESSION['graffitgroup'] = $groupid;

$url = $helper->getLoginUrl('https://' . $_SERVER['HTTP_HOST'] . '/facebook/facebook_response.php', $permissions);
#echo $url;
header('Location: ' . $url);