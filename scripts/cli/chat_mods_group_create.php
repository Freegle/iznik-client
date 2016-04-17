<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');

$opts = getopt('g:');
$gid = count($opts) > 0 ? $opts['g'] : NULL;

$sql = "SELECT id FROM groups " . ($gid ? " WHERE id = $gid" : "") . " ORDER BY nameshort ASC;";
$groups = $dbhr->query($sql);

$r = new ChatRoom($dbhr, $dbhm);

foreach ($groups as $group) {
    $g = new Group($dbhr, $dbhm, $group['id']);
    echo("Group #{$group['id']} " . $g->getPrivate('nameshort') . "\n");
    $r->create($g->getPrivate('nameshort') . ' Mods', $group['id'], TRUE, TRUE);
    $r->setPrivate('description', $g->getPrivate('nameshort') . ' Mods');
}
