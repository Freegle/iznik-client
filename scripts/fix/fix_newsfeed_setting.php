<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$groups = $dbhr->preQuery("SELECT id FROM groups;");
foreach ($groups as $group) {
    $g = new Group($dbhr, $dbhm, $group['id']);
    $rel = $g->getSetting('relevant', 1);

    $settings = json_decode($g->getPrivate('settings'), TRUE);
    $settings['newsfeed'] = $rel;
    $g->setSettings($settings);
    error_log($g->getPrivate('nameshort') . " ... $rel");
}