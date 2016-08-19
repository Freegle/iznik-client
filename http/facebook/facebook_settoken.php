<?php

session_start();

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

$id = presdef('id', $_REQUEST, NULL);
$token = presdef('token', $_REQUEST, NULL);

$fb = new Facebook\Facebook([
    'app_id' => FBGRAFFITIAPP_ID,
    'app_secret' => FBGRAFFITIAPP_SECRET
]);

if ($id && $token) {
    # We have to ensure that we are an admin for the page we've chosen, so check the list again.
    try {
        $accessToken = $_SESSION['fbaccesstoken'];
        #error_log("Got token from session $accessToken");

        $ret = $fb->get('/me/accounts', $accessToken);
        $accounts = $ret->getDecodedBody();
        #error_log("Got accounts " . var_export($accounts, TRUE));
        $pages = $accounts['data'];
        $found = FALSE;

        foreach ($pages as $page) {
            if ($page['id'] == $id) {
                $f = new GroupFacebook($dbhr, $dbhm);
                $gid = $f->findById($page['id']);

                if ($gid) {
                    echo "Found group and set access token.  You can close this tab now.";
                    $f = new GroupFacebook($dbhr, $dbhm, $gid);
                    $f->set($page['access_token']);
                    $found = TRUE;
                }
            }
        }

        if (!$found) {
            echo "Hmmm...couldn't find that page in your list.";
        }
    } catch (Exception $e) {
        echo "Something went wrong " . $e->getMessage();
    }
}
