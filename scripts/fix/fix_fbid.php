<?php


require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');

use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

$groups = $dbhr->preQuery("SELECT * FROM groups_facebook;");

$f = new GroupFacebook($dbhr, $dbhm);
$fb = $f->getFB();

foreach ($groups as $group) {
    try {
        if (!$group['id']) {
            $ret = $fb->get($group['name'], 'EAABo7zTHzCsBADWGwABloWnZC2YbguPmkKV4vsHkbsNZAJAqLJYmlybEzLLRK5piy5yzG1sKwzCBaqvRbeZCxrjB7VMeZCvTqL2CK8qsZAOb7lYTQiMDMw4HlUt240CVu0dX51YzZAydIZAPi4RNcutnjbZAQtdlCrGy464ZBDU43ZAgZDZD');
            $data = $ret->getDecodedBody();
            var_dump($data);
            $dbhm->preExec("UPDATE groups_facebook SET name = ?, id = ? WHERE groupid = ?;", [
                $data['name'],
                $data['id'],
                $group['groupid']
            ]);
        }
    } catch (Exception $e) {}
}