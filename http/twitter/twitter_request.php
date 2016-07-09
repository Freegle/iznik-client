<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Twitter.php');

use Abraham\TwitterOAuth\TwitterOAuth;

$groupid = intval(presdef('groupid', $_REQUEST, 0));
$t = new Twitter($dbhr, $dbhm, $groupid);

$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
$request_token = $connection->oauth("oauth/request_token", array("oauth_callback" => "https://{$_SERVER['HTTP_HOST']}/twitter/twitter_response.php?groupid=" . presdef('groupid', $_REQUEST, 0)));

# Save off the info we will need in the response function.
$t->set(NULL, $request_token['oauth_token'], $request_token['oauth_token_secret']);

$url = $connection->url("oauth/authorize", array("oauth_token" => $request_token['oauth_token']));

header('Location: ' . $url);
?>