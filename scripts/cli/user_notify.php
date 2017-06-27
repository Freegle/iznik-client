<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');

$opts = getopt('i:t:u:g:');

if (count($opts) < 2) {
    echo "Usage: hhvm user_notify.php (-i <user ID> or -g <group ID>) -t <type> (-u url)\n";
} else {
    $id = presdef('i', $opts, NULL);
    $gid = presdef('g', $opts, NULL);
    $type = $opts['t'];
    $url = $opts['u'];
    $n = new Notifications($dbhr, $dbhm);

    if ($id) {
        $n->add(NULL, $id, $type, NULL, $url);
    } else if ($gid) {
        if ($gid == -1) {
            $membs = $dbhr->preQuery("SELECT DISTINCT userid FROM memberships WHERE groupid IN (SELECT id FROM groups WHERE type = 'Freegle' AND publish = 1 AND onhere = 1) AND collection = ?;", [
                MembershipCollection::APPROVED
            ]);
        } else {
            $membs = $dbhr->preQuery("SELECT DISTINCT userid FROM memberships WHERE groupid = ? AND collection = ?;", [
                $gid,
                MembershipCollection::APPROVED
            ]);
        }

        $sendcount = 0;
        $skipcount = 0;
        $alreadycount = 0;

        foreach ($membs as $memb) {
            $u = new User($dbhr, $dbhm, $memb['userid']);

            if ($type == Notifications::TYPE_TRY_FEED) {
                # Don't send these too often.  Helps when notifying multiple groups
                $mysqltime = date("Y-m-d H:i:s", strtotime("midnight 7 days ago"));
                $recent = $dbhr->preQuery("SELECT * FROM users_notifications WHERE touser = ? AND type = ? AND timestamp > '$mysqltime';", [
                    $memb['userid'],
                    $type
                ]);

                if (count($recent) > 0) {
                    error_log($u->getEmailPreferred() . "..already");
                    $alreadycount++;
                    continue;
                }
            }

            $send = FALSE;
            $emails = $u->getEmails();
            foreach ($emails as $email) {
                if (ourDomain($email['email'])) {
                    $send = TRUE;
                }
            }

            if ($send) {
                error_log($u->getEmailPreferred() . "...send");
                $n->add(NULL, $memb['userid'], $type, NULL, $url);
                $sendcount++;
            } else {
                error_log($u->getEmailPreferred() . "..skip");
                $skipcount++;
            }
        }

        error_log("Sent to $sendcount, skipped $skipcount, already $alreadycount");
    }
}
