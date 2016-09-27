<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$opts = getopt('e:g:');

if (count($opts) < 2) {
    echo "Usage: hhvm user_checkdigest.php -e <email of user> -g <name of group>\n";
} else {
    $email = $opts['e'];
    $groupname = $opts['g'];

    $g = Group::get($dbhr, $dbhm);
    $gid = $g->findByShortName($groupname);

    if ($gid) {
        error_log("Found group #$gid");
        $g = Group::get($dbhr, $dbhm, $gid);
        $u = User::get($dbhr, $dbhm);
        $uid = $u->findByEmail($email);

        if ($uid) {
            # This logic here is similar to that in Digest::send, but with more logging.
            $u = User::get($dbhr, $dbhm, $uid);
            $email = $u->getEmailPreferred();
            error_log("Found user #$uid with preferred email $email");
            if ($email) {
                $sendit = FALSE;

                if ($g->getPrivate('onyahoo')) {
                    error_log("Group is on Yahoo");

                }

                $membershipmail = $u->getEmailForYahooGroup($gid, TRUE, TRUE)[1];

                if ($membershipmail) {
                    # They have a membership on Yahoo with one of our addresses.
                    error_log("Got membership mail $membershipmail");
                    $sendit = TRUE;
                    error_log("...ours? $sendit");
                } else {
                    # They don't have a membership on Yahoo with one of our addresses.  If we have sync'd our
                    # membership fairly recently, then we can rely on that and it means that we shouldn't send
                    # it.
                    $lastsync = $g->getPrivate('lastyahoomembersync');
                    $lastsync = $lastsync ? strtotime($lastsync) : NULL;
                    $age = $lastsync ? ((time() - $lastsync) / 3600) : NULL;
                    error_log("Last sync hours ago $age");

                    if (!$age || $age > 7 * 24) {
                        # We don't have a recent sync, because the mods aren't using ModTools regularly.
                        #
                        # Use email for them having any of ours as an approximation.
                        $emails = $u->getEmails();
                        foreach ($emails as $anemail) {
                            error_log("...check email {$anemail['email']}");
                            if (ourDomain($anemail['email'])) {
                                error_log("...ours");
                                $sendit = TRUE;
                            }
                        }
                    }
                }
            }

            error_log("Sendit? $sendit");

            if ($sendit) {
                # We might be on holiday.
                #error_log("...$email");
                $hol = $u->getPrivate('onholidaytill');
                $till = $hol ? strtotime($hol) : 0;

                if (time() > $till) {
                    error_log("Not on holiday - send");
                } else {
                    error_log("On holiday");
                }
            }
        } else {
            error_log("Failed to find user $email");
        }
    } else {
        error_log("Failed to find group $groupname");
    }
}

