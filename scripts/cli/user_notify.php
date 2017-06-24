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
        $membs = $dbhr->preQuery("SELECT DISTINCT userid FROM memberships WHERE groupid = ? AND collection = ?;", [
            $gid,
            MembershipCollection::APPROVED
        ]);

        $sendcount = 0;
        $skipcount = 0;

        foreach ($membs as $memb) {
            $u = new User($dbhr, $dbhm, $memb['userid']);
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

        error_log("Sent to $sendcount, skipped $skipcount");
    }
}
