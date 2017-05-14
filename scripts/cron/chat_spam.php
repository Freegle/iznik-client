<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$u = new User($dbhr, $dbhm);
$uid = $u->findByEmail(MODERATOR_EMAIL);

# We look for users who are not whitelisted, but where we have marked multiple chat messages from them
# as spam.  Exclude messages automarked as spam.
$users = $dbhr->preQuery("SELECT DISTINCT chat_messages.userid, COUNT(*) AS count FROM chat_messages LEFT JOIN spam_users ON spam_users.userid = chat_messages.userid INNER JOIN users ON users.id = chat_messages.userid WHERE reviewrejected = 1 AND reviewrejected != $uid (collection IS NULL OR collection != 'Whitelisted') AND systemrole = 'User' GROUP BY chat_messages.userid HAVING count > 5  ORDER BY count DESC;");
$count = 0;

foreach ($users as $user) {
    # Check whether we have ever marked something from them as not spam.  If we have, then they might be being
    # spoofed and unlucky.  If not, these are almost certainly spammers, so we will auto mark any chat messages
    # currently held for review as spam.  We don't add them to the spammer list because removing someone from that
    # if it was a mistake is a pain.
    $ok = $dbhr->preQuery("SELECT COUNT(*) AS count FROM chat_messages WHERE userid = ? AND reviewrequired = 1 AND reviewedby IS NOT NULL AND reviewrejected = 0;", [
        $user['userid']
    ]);

    #error_log("...{$user['userid']} ok count {$ok[0]['count']}");
    if ($ok[0]['count'] == 0) {
        $reviews = $dbhr->preQuery("SELECT COUNT(*) AS count FROM chat_messages WHERE userid = ? AND reviewrequired = 1 AND reviewedby IS NULL;", [
            $user['userid']
        ]);

        if ($reviews[0]['count'] > 0) {
            error_log("...{$user['userid']} spam count {$user['count']} marked as spam, auto-mark {$reviews[0]['count']} pending review");
            $count += $reviews[0]['count'];
            $dbhm->preExec("UPDATE chat_messages SET reviewrequired = 0, reviewedby = ? WHERE userid = ? AND reviewrequired = 1 AND reviewedby IS NULL;", [
                $uid,
                $user['userid']
            ]);
        }
    }
}

error_log("Auto-marked $count");