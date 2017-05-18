<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$m = new Message($dbhr, $dbhm);

$messages = $dbhr->preQuery("SELECT id, subject FROM messages;");
$count = 0;

foreach ($messages as $message) {
    $subject = $message['subject'];
    $subject = trim(preg_replace('/^\[.*?\]\s*/', '', $subject));

    if (strlen($subject) === 0) {
        $items = $dbhr->preQuery("SELECT * FROM messages_items WHERE msgid = ?;", [
            $message['id']
        ]);

        foreach ($items as $item) {
            $m = new Message($dbhr, $dbhm, $message['id']);
            $groupids = $m->getGroups(FALSE, TRUE);
            foreach ($groupids as $groupid) {
                $m->constructSubject($groupid);
                error_log($message['id'] . " => " . $m->getSubject());
            }
        }
    }

    $count++;

    if ($count % 1000 === 0) {
        error_log("...$count / " . count($messages));
    }
}
