<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Message.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

$l = new Log($dbhr, $dbhm);

# Look for messages which have been pending for too long.  This fallback catches cases where the message doesn't
# reach Yahoo properly, or the group is not being regularly moderated.
$sql = "SELECT msgid, groupid, TIMESTAMPDIFF(HOUR, messages_groups.arrival, NOW()) AS ago FROM messages_groups INNER JOIN messages ON messages.id = messages_groups.msgid WHERE collection = ? AND heldby IS NULL HAVING ago > 48;";
$messages = $dbhr->preQuery($sql, [
    MessageCollection::PENDING
]);

foreach ($messages as $message) {
    $m = new Message($dbhr, $dbhm, $message['msgid']);
    error_log("{$message['msgid']} has been pending for {$message['ago']} - auto approve message");
    $m->approve($message['groupid']);

    $l->log([
        'type' => Log::TYPE_MESSAGE,
        'subtype' => Log::SUBTYPE_AUTO_APPROVED,
        'groupid' => $message['groupid'],
        'msgid' => $message['msgid']
    ]);
}

# Look for messages which were queued and can now be sent.  This fallback catches cases where Yahoo doesn't let us know
# that someone is now a member, but we find out via other means (e.g. plugin) or time out waiting.
$sql = "SELECT messages.id, messages.date, TIMESTAMPDIFF(HOUR, messages_groups.arrival, NOW()) AS ago, messages.subject, messages.fromaddr, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages_groups.msgid = messages.id LEFT OUTER JOIN messages_outcomes ON messages_outcomes.msgid = messages.id WHERE collection = ? AND messages_outcomes.msgid IS NULL;";
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

                # If the message has been hanging around for a while, it might be that Yahoo has blocked our
                # subscribe request - we've seen messages get delayed for many days with 451 temp errors.
                # In that case assume the membership worked (which it might have done) and submit the message.
                # The message will then be in Pending, and we have more processing below to auto-approve those mails
                # if they are not moderated in time.
                if ($message['ago'] >= 48 && $u->isPendingMember($message['groupid']) && !$u->isHeld($message['groupid'])) {
                    error_log("{$message['id']} has been pending for {$message['ago']} - auto approve membership");
                    $eid = $u->getOurEmailId();
                    $u->markYahooApproved($message['groupid'], $eid);
                    $l->log([
                        'type' => Log::TYPE_GROUP,
                        'subtype' => Log::SUBTYPE_AUTO_APPROVED,
                        'groupid' => $message['groupid'],
                        'msgid' => $message['id']
                    ]);
                }

                #error_log("Email $email id $eid for {$message['groupid']}");

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
