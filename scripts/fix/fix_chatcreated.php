<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');

$rooms = $dbhr->preQuery("SELECT * FROM chat_rooms");

foreach ($rooms as $room) {
    $messages = $dbhr->preQuery("SELECT date FROM chat_messages WHERE chatid = ? ORDER BY date ASC LIMIT 1;", [ $room['id']]);
    if (count($messages) > 0) {
        $dbhm->preExec("UPDATE chat_rooms SET created = ? WHERE id = ?;", [ $messages[0]['date'], $room['id']] );
    }
}
