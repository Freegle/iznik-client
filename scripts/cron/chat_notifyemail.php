<?php
# Notify by email of unread chats

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

# TODO Other types?
$c = new ChatRoom($dbhr, $dbhm);
$c->notifyByEmail(NULL, ChatRoom::TYPE_USER2USER, TRUE);

unlockScript($lockh);