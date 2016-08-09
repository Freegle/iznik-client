<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$groups = $dbhr->preQuery("SELECT id FROM groups;");

foreach ($groups as $group) {
    $g = new Group($dbhr, $dbhm, $group['id']);
    $settings = json_decode($g->getPrivate('settings'), TRUE);
    unset($settings['spammers']['checkreview']);
    $settings['spammers']['chatreview'] = $g->getPrivate('type') == Group::GROUP_FREEGLE ? 1 : 0;
    $g->setSettings($settings);
}
