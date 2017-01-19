<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');

$messages = $dbhr->preQuery("SELECT * FROM messages WHERE id = 14170415;");
$m = new ChatMessage($dbhr, $dbhm);

foreach ($messages as $message) {
    error_log("Check spam? " . $m->checkSpam($message['textbody']));
    error_log("Check review? " . $m->checkReview($message['textbody']));
}
