<?php
# Rescale large images in message_attachments

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');

$messages = $dbhr->preQuery("SELECT * FROM chat_messages");
$total = count($messages);
$count = 0;
$spam = 0;
$ham = 0;
$m = new ChatMessage($dbhr, $dbhm);

foreach ($messages as $message) {
    $snippet = substr($message['message'], 0, 80);
    if (preg_match('/(.*)$/', $snippet, $matches)) {
        $snippet = $matches[1];
    }

    $review = $m->checkReview($message['message']);

    if ($review) {
        $spam++;
    } else {
        $ham++;
    }

    $count++;

    if ($review && !$message['reviewrequired']) {
        error_log("New review for #{$message['id']} review $review snippet $snippet");
        $dbhm->preExec("UPDATE chat_messages SET reviewrequired = 1, reviewedby = NULL, reviewrejected = 0 WHERE id = ?;", [ $message['id']]);
    } else if (!$review && $message['reviewrequired']) {
        error_log("No longer review for #{$message['id']} review $review snippet $snippet");
        $dbhm->preExec("UPDATE chat_messages SET reviewrequired = 0, reviewedby = NULL, reviewrejected = 0 WHERE id = ?;", [ $message['id']]);
    }
}

error_log("$count messages, $spam spam, $ham ham");