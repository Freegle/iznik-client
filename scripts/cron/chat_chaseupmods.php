<?php
# Notify by email of unread chats

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$c = new ChatRoom($dbhr, $dbhm);

$chats = $c->chaseupMods();
$count = count($chats);
error_log("Chased up $count groups for User2Mod");

unlockScript($lockh);