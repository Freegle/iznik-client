<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsnfd = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = new Group($dbhr, $dbhm);
$u = new User($dbhr, $dbhm);

if (1==0) {
    error_log("Migrate FD users");
    $count = 0;

    $users = $dbhfd->query("SELECT * FROM facebook");

    error_log("Queried");

    foreach ($users as $user) {
        try {
            $eid = $u->findByEmail($user['email']);
            #error_log("{$user['email']} = $eid");

            if (!$eid) {
                $uid = $u->create(NULL, NULL, $user['facebookname'], 'Migrated from FD');
            } else {
                $u = new User($dbhr, $dbhm, $eid);
                $u->setPrivate('fullname', $user['facebookname']);
            }

            $count++;
            if ($count % 1000 == 0) {
                error_log("...$count");
            }
        } catch (Exception $e) {
            error_log("Skip FD facebook table {$user['fduniqueid']} with " . $e->getMessage());
        }
    }

    $dbhm->exec("update users set fullname = null where fullname = '';");
}

if (1==0) {
    $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => FALSE
    ));

    error_log("Migrate FD memberships");
    $groups = $dbhfd->query("SELECT * FROM groups WHERE grouppublish = 1;");
    $groupcount = 0;

    foreach ($groups as $group) {
        $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => FALSE
        ));

        $groupcount++;
        error_log("Migrate FD #$groupcount - {$group['groupname']}");
        $gid = $g->findByShortName($group['groupname']);

        if ($gid) {
            $g = new Group($dbhr, $dbhm, $gid);

            # Only add users who have joined recently.  This means we won't readd old members that have not been
            # removed from Iznik yet because there hasn't been a member sync.
            $mysqltime = date ("Y-m-d", strtotime("48 hours ago"));

            $users = $dbhfd->query("SELECT * FROM users WHERE groupid = {$group['groupid']} AND dateinserted >= '$mysqltime' AND deletedfromyahoo = 0;");
            $count = 0;
            foreach ($users as $user) {
                try {
                    $eid1 = $u->findByEmail($user['useremail']);
                    $eid2 = $u->findByEmail($user['groupsemail']);
                    $yid = $u->findByYahooId($user['yahooid']);
                    $p = strpos($user['useremail'], '@');
                    $name = $user['yahooid'] && strlen($user['yahooid']) > 0 ? $user['yahooid'] : substr($user['useremail'], 0, $p);
                    error_log("{$user['useremail']} = $eid1, {$user['groupsemail']} = $eid2, {$user['yahooid']} = $yid");

                    $id = $eid1 ? $eid1 : ($eid2 ? $eid2 : $yid);
                    $reason = "MigrateMembers - email {$user['useremail']} = $eid1, email {$user['groupsemail']} = $eid2, YahooId {$user['yahooid']} = $yid";

                    if (!$id) {
                        # Unknown user.  Create
                        $id = $u->create(NULL, NULL, $name, "Migrated from FD");
                    }

                    if ($eid1 && $eid1 != $id) {
                        $u->merge($id, $eid1, $reason);
                    }

                    if ($eid2 && $eid2 != $id) {
                        $u->merge($id, $eid2, $reason);
                    }

                    if ($yid && $yid != $id) {
                        $u->merge($id, $yid, $reason);
                    }

                    $u = new User($dbhr, $dbhm, $id);
                    $emailid = $u->addEmail($user['useremail'], 0, FALSE);
                    $emailid = $u->addEmail($user['groupsemail'], 0, FALSE);
                    $membs = $u->getMemberships();

                    $already = FALSE;
                    foreach ($membs as $m) {
                        if ($m['id'] == $gid) {
                            $already = TRUE;
                        }
                    }

                    if (!$already) {
                        error_log("Add membership to $gid with email $emailid for {$user['useremail']}");
                        $u->addMembership($gid, User::ROLE_MEMBER, $emailid, MembershipCollection::APPROVED);
                    }

                    $copy = [
                        'yahooid' => 'yahooid'
                    ];

                    foreach ($copy as $old => $new) {
                        if ($user[$old] && strlen($user[$old]) > 0) {
                            $u->setPrivate($new, $user[$old]);
                        }
                    }

                    $dig = $user['digest'] ? $user['maxdigestdelay'] : 0;
                    $u->setMembershipAtt($gid, 'emailfrequency', $dig);
                    $events = $user['eventsdisabled'] ? 0 : 1;
                    $u->setMembershipAtt($gid, 'eventsallowed', $events);

                    $fbusers = $dbhfd->query("SELECT * FROM facebook WHERE email = " . $dbhfd->quote($user['useremail']));
                    foreach ($fbusers as $fbuser) {
                        $hol = presdef('onholidaytill', $fbuser, NULL);
                        $hol = ($hol && $hol != '0000-00-00') ? $hol : NULL;
                        $u->setPrivate('onholidaytill', $hol);
                    }

                    $count++;
                    if ($count % 1000 == 0) {
                        error_log("...$count");
                    }
                } catch (Exception $e) {
                    error_log("Skip FD {$user['uniqueid']} with " . $e->getMessage());
                }
            }
        } else {
            error_log("...not on Iznik");
        }
    }
}

$dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

error_log("Migrate deleted FD memberships");
$groups = $dbhfd->query("SELECT * FROM groups WHERE grouppublish = 1;");
$groupcount = 0;

foreach ($groups as $group) {
    $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => FALSE
    ));

    $groupcount++;
    error_log("Migrate deleted FD #$groupcount - {$group['groupname']}");
    $gid = $g->findByShortName($group['groupname']);

    if ($gid) {
        $g = new Group($dbhr, $dbhm, $gid);

        $lastsync = $g->getPrivate('lastyahoomembersync');
        $lastsync = $lastsync ? strtotime($lastsync) : NULL;
        $age = $lastsync ? ((time() - $lastsync) / 3600) : NULL;

        if (!$age || $age > 7 * 24) {
            # Only add users who have joined recently.  This means we won't readd old members that have not been
            # removed from Iznik yet because there hasn't been a member sync.
            $mysqltime = date("Y-m-d", strtotime("48 hours ago"));

            $users = $dbhfd->query("SELECT * FROM users WHERE groupid = {$group['groupid']} AND deletedfromyahoo = 1;");
            $count = 0;
            foreach ($users as $user) {
                $eid = $u->findByEmail($user['useremail']);
                if ($eid) {
                    # Check for specific email
                    $sql = "SELECT memberships_yahoo.id, membershipid FROM memberships_yahoo INNER JOIN users_emails ON memberships_yahoo.emailid = users_emails.id INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE users_emails.email LIKE ? AND groupid = ?;";
                    #error_log("$sql, {$user['useremail']}, $gid");
                    $delid = NULL;
                    $membid = NULL;
                    $membs = $dbhr->preQuery($sql, [$user['useremail'], $gid]);
                    foreach ($membs as $memb) {
                        $delid = $memb['id'];
                        $membid = $memb['membershipid'];
                        error_log("#$eid {$user['useremail']} is member of #$gid {$group['groupname']} with emailid but deleted on Yahoo");
                    }

                    if (!$delid) {
                        # Check for membership with no specific email
                        $sql = "SELECT memberships_yahoo.id, membershipid FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE memberships.userid = ? AND groupid = ? AND memberships_yahoo.emailid IS NULL;";
                        #error_log("$sql, $eid, $gid");
                        $membs = $dbhr->preQuery($sql, [ $eid, $gid ]);
                        foreach ($membs as $memb) {
                            $delid = $memb['id'];
                            $membid = $memb['membershipid'];
                            error_log("#$eid {$user['useremail']} is member of #$gid {$group['groupname']} without emailid but deleted on Yahoo");
                        }
                    }

                    if ($delid) {
                        $sql = $dbhm->preExec("DELETE FROM memberships_yahoo WHERE id = ?;", [ $delid ]);
                        $others = $dbhm->preQuery("SELECT * FROM memberships_yahoo WHERE membershipid = ?;", [ $membid ]);

                        if (count($others) == 0) {
                            $dbhm->preExec("DELETE FROM memberships WHERE id = ?;", [ $membid ]);
                        } else {
                            error_log("Other memberships");
                        }
                    }
                }
            }
        }
    }
}