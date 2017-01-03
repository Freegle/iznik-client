<?php

session_start();

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

$id = presdef('id', $_REQUEST, NULL);
$token = presdef('token', $_REQUEST, NULL);
$type = presdef('graffititype', $_SESSION, 'Page');

$fb = new Facebook\Facebook([
    'app_id' => $type == 'Page' ? FBGRAFFITIAPP_ID : FBAPP_ID,
    'app_secret' => $type == 'Page' ? FBGRAFFITIAPP_SECRET : FBAPP_SECRET
]);

if ($id && $token) {
    # We have to ensure that we are an admin for the page we've chosen, so check the list again.
    try {
        $accessToken = $_SESSION['fbaccesstoken'];
        #error_log("Got token from session $accessToken");

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

            $found = FALSE;

            foreach ($totalPages as $page) {
                #echo("Compare {$page['id']} vs $id");
                if (strcmp($page['id'], $id) === 0) {
                    $f = new GroupFacebook($dbhr, $dbhm);
                    $gid = presdef('graffitigroup', $_SESSION, NULL);

                    if ($gid) {
                        echo "Found group.  You can close this tab now.";
                        $f = new GroupFacebook($dbhr, $dbhm, $gid);
                        $f->set($gid, $page['access_token'], $page['name'], $page['id'], GroupFacebook::TYPE_PAGE);
                        $found = TRUE;
                    }
                }
            }

            if (!$found) {
                echo "Hmmm...couldn't find that page in your list.";
            }
        } else {
            $pages = [];
            $url = '/me/groups';
            $getPages = $fb->get($url, $accessToken);
            $pages = $getPages->getGraphEdge();

            $totalGroups = array();

            if ($fb->next($pages)) {
                $pagesArray = $pages->asArray();
                $totalGroups = array_merge($totalGroups, $pagesArray);
                while ($pages = $fb->next($pages)) {
                    $pagesArray = $pages->asArray();
                    $totalGroups = array_merge($totalGroups, $pagesArray);
                }
            } else {
                $pagesArray = $pages->asArray();
                $totalGroups = array_merge($totalGroups, $pagesArray);
            }

            usort($totalGroups, function ($a, $b) {
                return (strcmp($a['name'], $b['name']));
            });

            $found = FALSE;

            foreach ($totalGroups as $group) {
                #echo("Compare {$page['id']} vs $id");
                if (strcmp($group['id'], $id) === 0) {
                    $f = new GroupFacebook($dbhr, $dbhm);
                    $gid = presdef('graffitigroup', $_SESSION, NULL);

                    if ($gid) {
                        echo "Found group.  You can close this tab now.";
                        $f = new GroupFacebook($dbhr, $dbhm, $gid);
                        $f->set($gid, $accessToken, $group['name'], $group['id'], GroupFacebook::TYPE_GROUP);
                        $found = TRUE;
                    }
                }
            }

            if (!$found) {
                echo "Hmmm...couldn't find that group in your list.";
            }
        }
    } catch (Exception $e) {
        echo "Something went wrong " . $e->getMessage();
    }
}
