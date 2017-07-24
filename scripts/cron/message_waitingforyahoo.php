<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/user/User.php');

# Look for messages which were queued and can now be sent.  This fallback catches cases where Yahoo doesn't let us know
# that someone is now a member, but we find out via other means (e.g. plugin).

$sql = "SELECT messages.id, messages.date, messages.subject, messages.fromaddr, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id LEFT OUTER JOIN messages_outcomes ON messages_outcomes.msgid = messages.id WHERE collection = ? AND messages_outcomes.msgid IS NULL;";
$messages = $dbhr->preQuery($sql, [
    MessageCollection::QUEUED_YAHOO_USER
]);

$submitted = 0;
$queued = 0;
$rejected = 0;

foreach ($messages as $message) {
    try {
        $m = new Message($dbhr, $dbhm, $message['id']);
        $outcome = $m->hasOutcome();

        if ($outcome) {
            # The message has been marked as completed, or withdrawn, while we were waiting for Yahoo to get its
            # act together.  Either way, there's no need to submit to Yahoo.
            #
            # Mark all of these as deleted.  That might not be quite right, but it gets them out of the way.
            $m->delete("Yahoo membership approved after message completed", $message['groupid']);
        } else {
            $uid = $m->getFromuser();

            if ($uid) {
                $u = User::get($dbhr, $dbhm, $uid);
                list ($eid, $email) = $u->getEmailForYahooGroup($message['groupid'], TRUE, TRUE);

                if ($eid) {
                    # Now approved - we can submit.
                    $m->submit($u, $email, $message['groupid']);
                    $outcome = ' submitted';
                    $submitted++;
                } else if (!$u->isRejected($message['groupid'])) {
                    # Still pending - maybe Yahoo lost it.  Resend the application.
                    $u->triggerYahooApplication($message['groupid'], FALSE);
                    $outcome = ' still queued';
                    $queued++;
                } else {
                    # No longer pending.  Just leave - it will eventually get purged, and this way we have it
                    # for debug if we want.
                    $outcome = ' rejected?';
                    $rejected++;
                }

                error_log("#{$message['id']} {$message['date']} {$message['subject']} $outcome");
            }
        }
    } catch (Exception $e) {
        error_log("Failed with " . $e->getMessage());
    }
}

error_log("\r\nSubmitted $submitted still queued $queued rejected $rejected");