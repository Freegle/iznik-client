<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$users = $dbhr->preQuery("select userid, count(*) as count from messages_outcomes inner join users on users.id = userid and systemrole = 'Moderator' and outcome = 'Taken' group by userid order by count desc limit 50;");

foreach ($users as $user) {
    $u = new User($dbhr, $dbhm, $user['userid']);
    error_log("{$user['userid']} " . $u->getName() . " " . $u->getEmailPreferred());
    $messages = $dbhr->preQuery("SELECT DISTINCT msgid, messages.date, subject FROM messages_outcomes INNER JOIN messages ON messages.id = messages_outcomes.msgid INNER JOIN chat_messages ON chat_messages.refmsgid = messages.id AND chat_messages.type = ? WHERE outcome = ? AND chat_messages.userid = ? AND messages_outcomes.userid = ? AND messages_outcomes.userid != messages.fromuser;", [
        ChatMessage::TYPE_INTERESTED,
        Message::OUTCOME_TAKEN,
        $user['userid'],
        $user['userid']
    ]);

    foreach ($messages as $message) {
        error_log("  {$message['msgid']} {$message['date']} {$message['subject']}");
    }
}