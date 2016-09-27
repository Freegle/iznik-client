<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# Look for messages which were queued and can now be sent.  This fallback catches cases where Yahoo doesn't let us know
# that someone is now a member, but we find out via other means (e.g. plugin).

$sql = "SELECT messages.id, messages.date, messages.subject, messages.fromaddr, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id WHERE  collection =  'QueuedYahooUser';";
$messages = $dbhr->preQuery($sql);

$submitted = 0;
$queued = 0;

foreach ($messages as $message) {
    $m = new Message($dbhr, $dbhm, $message['id']);

    $uid = $m->getFromuser();

    if ($uid) {
        $u = User::get($dbhr, $dbhm, $uid);
        list ($eid, $email) = $u->getEmailForYahooGroup($message['groupid'], TRUE, TRUE);

        if ($eid) {
            $m->submit($u, $email, $message['groupid']);
            $outcome = ' submitted';
            $submitted++;
        } else {
            $u->triggerYahooApplication($message['groupid'], FALSE);
            $outcome = ' still queued';
            $queued++;
        }

        error_log("#{$message['id']} {$message['date']} {$message['subject']} $outcome");
    }
}

error_log("\r\nSubmitted $submitted still queued $queued");