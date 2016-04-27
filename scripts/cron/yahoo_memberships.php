<?php
#
# Purge logs. We do this in a script rather than an event because we want to chunk it, otherwise we can hang the
# cluster with an op that's too big.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# Yahoo is flaky.  Find messages which are awaiting a Yahoo membership, and apply again.  This may kick Yahoo into
# life and let us know that we are actually a member (which will trigger submission of the message).
$msgs = $dbhr->query("SELECT messages.fromuser, messages_groups.groupid, groups.nameshort FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id INNER JOIN groups ON groups.id = messages_groups.groupid WHERE messages_groups.collection = 'QueuedYahooUser';");
foreach ($msgs as $msg) {
    $u = new User($dbhr, $dbhm, $msg['fromuser']);
    list ($eid, $email) = $u->getEmailForYahooGroup($msg['groupid'], TRUE);
    error_log("...group {$msg['nameshort']} user #{$msg['fromuser']} email {$email}");
    $headers = "From: $email>\r\n";
    mail($email['nameshort'] . "-subscribe@yahoogroups.com", "Please let me join", "Pretty please", $headers, "-f$email");
}