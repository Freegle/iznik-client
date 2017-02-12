<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# Look for messages which never got submitted to Yahoo.  This can happen rarely in crash situations.
$sql = "SELECT messages_groups.* FROM messages_groups INNER JOIN groups ON messages_groups.groupid = groups.id AND onyahoo = 1 WHERE collection = 'Approved' AND yahooapprovedid IS NULL AND yahoopendingid IS NULL AND deleted = 0 AND senttoyahoo = 0 AND arrival <= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND arrival >= DATE_SUB(NOW(), INTERVAL 48 HOUR);";
$messages = $dbhr->preQuery($sql);

$submitted = 0;
$queued = 0;
$rejected = 0;

foreach ($messages as $message) {
    $m = new Message($dbhr, $dbhm, $message['msgid']);

    $uid = $m->getFromuser();

    if ($uid) {
        $u = User::get($dbhr, $dbhm, $uid);
        list ($eid, $email) = $u->getEmailForYahooGroup($message['groupid'], TRUE, TRUE);

        if ($eid) {
            $m->submit($u, $email, $message['groupid']);
            $outcome = ' submitted';
            $submitted++;
        } else if ($u->isPending($message['groupid'])) {
            $u->triggerYahooApplication($message['groupid'], FALSE);
            $outcome = ' still queued';
            $queued++;
        } else {
            # No longer pending.  Just leave - it will eventually get purged, and this way we have it
            # for debug if we want.
            $outcome = ' rejected?';
            $rejected++;
        }

        error_log("#{$message['msgid']} {$message['arrival']} $outcome");
    }
}

error_log("\r\nSubmitted $submitted still queued $queued rejected $rejected");