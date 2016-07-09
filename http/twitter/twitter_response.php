<?php

require_once dirname(__file__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Twitter.php');

use Abraham\TwitterOAuth\TwitterOAuth;

$groupid = intval(presdef('groupid', $_REQUEST, 0));
$t = new Twitter($dbhr, $dbhm, $groupid);

try {
    $oauth_verifier = presdef('oauth_verifier', $_REQUEST, NULL);

    $atts = $t->getPublic();

    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $atts['token'], $atts['secret']);
    $access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $oauth_verifier));

    $accesstoken = $access_token['oauth_token'];
    $secrettoken = $access_token['oauth_token_secret'];
    $name = $access_token['screen_name'];

    $t->set($name, $accesstoken, $secrettoken);

    echo "Thanks - you can close this tab now.";
} catch (Exception $e) {
    echo "This didn't work - " . $e->getMessage();
    var_dump($_REQUEST);
}

?>