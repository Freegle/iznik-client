<?php

session_start();

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

$groupid = presdef('graffitigroup', $_SESSION, 0);

$fb = new Facebook\Facebook([
    'app_id' => FBGRAFFITIAPP_ID,
    'app_secret' => FBGRAFFITIAPP_SECRET
]);

$helper = $fb->getRedirectLoginHelper();

try {
    $accessToken = $helper->getAccessToken();
    $_SESSION['fbaccesstoken'] = (string)$accessToken;

    $ret = $fb->get('/me', $accessToken);
    $pages = [];
    $url = '/me/accounts';

    # Page through all of them.
    do {
        $ret = $fb->get($url, $accessToken);
        $accounts = $ret->getDecodedBody();
        error_log("Got accounts " . var_export($accounts, TRUE));

        if (pres('data', $accounts)) {
            $pages = array_merge($pages, $accounts['data']);
        }

        $url = pres('paging', $accounts) ? presdef('next', $accounts['paging'], NULL) : NULL;
        error_log("Next url $url");
    } while ($url);
    $found = FALSE;

    usort($pages, function ($a, $b) {
        return (strcmp($a['name'], $b['name']));
    });

    ?>
    <p>These are the Facebook pages you manage.  Click on the one you want to link to your group.</p>
    <?php
    foreach ($pages as $page) {
        echo '<a href="/facebook/facebook_settoken.php?id=' . urlencode($page['id']) . '&token=' . urlencode($page['access_token']) . '">' . $page['name'] . '</a><br />';
    }
} catch(Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

if (! isset($accessToken)) {
    if ($helper->getError()) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Error: " . $helper->getError() . "\n";
        echo "Error Code: " . $helper->getErrorCode() . "\n";
        echo "Error Reason: " . $helper->getErrorReason() . "\n";
        echo "Error Description: " . $helper->getErrorDescription() . "\n";
    } else {
        header('HTTP/1.0 400 Bad Request');
        echo 'Bad request';
    }
    exit;
}