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

$fb = new Facebook\Facebook([
    'app_id' => FBGRAFFITIAPP_ID,
    'app_secret' => FBGRAFFITIAPP_SECRET
]);

$helper = $fb->getRedirectLoginHelper();

try {
    $accessToken = $helper->getAccessToken();
    $_SESSION['fbaccesstoken'] = (string)$accessToken;

    $ret = $fb->get('/me', $accessToken);

    if ($type == 'Page') {
        $pages = [];
        $url = '/me/accounts';
        $getPages = $fb->get($url, $accessToken);
        $pages = $getPages->getGraphEdge();

        $totalPages = array();

        if ($fb->next($pages)) {
            $pagesArray = $pages->asArray();
            $totalPages = array_merge($totalPages, $pagesArray);
            while ($pages = $fb->next($pages)) {
                $pagesArray = $pages->asArray();
                $totalPages = array_merge($totalPages, $pagesArray);
            }
        } else {
            $pagesArray = $pages->asArray();
            $totalPages = array_merge($totalPages, $pagesArray);
        }

        usort($totalPages, function ($a, $b) {
            return (strcmp($a['name'], $b['name']));
        });
        ?>
        <p>These are the Facebook pages you manage.  Click on the one you want to link to your group.</p>
        <?php
        foreach ($totalPages as $page) {
            echo '<a href="/facebook/facebook_settoken.php?id=' . urlencode($page['id']) . '&token=' . urlencode($page['access_token']) . '">' . $page['name'] . '</a><br />';
        }
    } else {
        # Find Facebook groups near the group
//        ?><!--<p>These are suggested groups you could link to.  Click on the ones you want to link to your group.  You can link multiple groups.</p>--><?php
//        $keywords = [ 'free', 'buy', 'sell', 'freebies'];
//
//        $totalGroups = [];
//
//        foreach ($keywords as $keyword) {
//            $ret = $fb->get("/search?q=$keyword&type=group&limit=20", $accessToken);
//            $groups = $ret->getGraphEdge();
//
//            if ($fb->next($groups)) {
//                $groupsArray = $groups->asArray();
//
//                $totalGroups = array_merge($totalGroups, $groupsArray);
//                while ($groups = $fb->next($groups)) {
//                    $groupsArray = $groups->asArray();
//                    $totalGroups = array_merge($totalGroups, $groupsArray);
//                }
//            } else {
//                $groupsArray = $groups->asArray();
//                $totalGroups = array_merge($totalGroups, $groupsArray);
//            }
//        }
//
//        foreach ($totalGroups as $group) {
//            if ($group['privacy'] == 'OPEN') {
//                print($group['name'] . '<br />');
//            }
//        }
        ?><p>Paste in the URL of a buy and sell group, e.g. https://www.facebook.com/groups/282100418467107/</p>
        <p>If your URL doesn't have a number in it, do View Source and search for group_id to find it.</p>
        <form action="https://<?php echo SITE_HOST; ?>/facebook/facebook_group.php?type=Group">
            <input type="text" name="url" placeholder="Enter the URL"/>
            <input type="submit"/>
        </form>
        <?php
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
