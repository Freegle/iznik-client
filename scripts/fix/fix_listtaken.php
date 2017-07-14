<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$users = $dbhr->preQuery("select userid, count(*) as count from messages_outcomes inner join users on users.id = userid and systemrole = 'Moderator' and outcome = 'Taken' group by userid order by count desc limit 50;");

foreach ($users as $user) {
    $u = new User($dbhr, $dbhm, $user['userid']);
    error_log("{$user['userid']} " . $u->getName() . " " . $u->getEmailPreferred());
    $messages = $dbhr->preQuery("select distinct msgid, date, subject from messages_outcomes inner join messages on messages.id = messages_outcomes.msgid WHERE outcome = ? AND userid = ?;", [
        Message::OUTCOME_TAKEN,
        $user['userid']
    ]);

    foreach ($messages as $message) {
        error_log("  {$message['msgid']} {$message['date']} {$message['subject']}");
    }
}