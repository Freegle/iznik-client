<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsnmt = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhmt = new PDO($dsnmt, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$dsnfd = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = new Group($dbhr, $dbhm);
$u = new User($dbhr, $dbhm);

$groupq = " LIKE '%ribble%' ";

error_log("Migrate FD users");
$count = 0;

$users = $dbhfd->query("SELECT * FROM facebook");

error_log("Queried");

if (1==0) {
    foreach ($users as $user) {
        try {
            $eid = $u->findByEmail($user['email']);
            #error_log("{$user['email']} = $eid");

            if (!$eid) {
                $uid = $u->create(NULL, NULL, $user['facebookname'], 'Migrated from FD');
            } else {
                $u = new User($dbhr, $dbhm, $eid);
                $u->setPrivate('fullname', $user['facebookname']);

                # Make sure it's primary
                $u->addEmail($user['email'], 1);
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

if (1==1) {
    $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => FALSE
    ));

    error_log("Migrate FD memberships");
    $groups = $dbhfd->query("SELECT * FROM groups WHERE grouppublish = 1;");

    foreach ($groups as $group) {
        $dbhmt = new PDO($dsnmt, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => FALSE
        ));

        $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => FALSE
        ));

        error_log("Migrate FD {$group['groupname']}");
        $gid = $g->findByShortName($group['groupname']);

        if ($gid) {
            $g = new Group($dbhr, $dbhm, $gid);

            $users = $dbhfd->query("SELECT * FROM users WHERE groupid = {$group['groupid']} AND deletedfromyahoo = 0;");
            $count = 0;
            foreach ($users as $user) {
                try {
                    $eid1 = $u->findByEmail($user['useremail']);
                    $eid2 = $u->findByEmail($user['groupsemail']);
                    $yid = $u->findByYahooId($user['yahooid']);
                    $p = strpos($user['useremail'], '@');
                    $name = $user['yahooid'] && strlen($user['yahooid']) > 0 ? $user['yahooid'] : substr($user['useremail'], 0, $p);
                    #error_log("{$user['useremail']} = $eid1, {$user['groupsemail']} = $eid2, {$user['yahooid']} = $yid");

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
                    $emailid = $u->addEmail($user['useremail'], 1);
                    $emailid = $u->addEmail($user['groupsemail'], 0);
                    $membs = $u->getMemberships();

                    $already = FALSE;
                    foreach ($membs as $m) {
                        if ($m['id'] == $gid) {
                            $already = TRUE;
                        }
                    }

                    if (!$already) {
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

# Now migrate ModTools users.
error_log("Migrate ModTools users");
$groups = $dbhmt->query("SELECT * FROM groups WHERE groupname;");

foreach ($groups as $group) {
    error_log("Migrate MT {$group['groupname']}");
    $dbhmt = new PDO($dsnmt, $dbconfig['user'], $dbconfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => FALSE
    ));

    $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => FALSE
    ));

    $gid = $g->findByShortName($group['groupname']);

    if ($gid) {
        $g = new Group($dbhr, $dbhm, $gid);

        $users = $dbhmt->query("SELECT * FROM members WHERE groupid = {$group['groupid']} AND memberStatus = 'CONFIRMED';");
        $count = 0;
        foreach ($users as $user) {
            try {
                $eid = $u->findByEmail($user['email']);
                $yid = $u->findByYahooId($user['yid']);
                $uid = $u->findByYahooUserId($user['userId']);
                $p = strpos($user['email'], '@');
                $name = $user['yid'] && strlen($user['yid']) > 0 ? $user['yid'] : substr($user['email'], 0, $p);

                $id = $eid ? $eid : ($uid ? $uid : $yid);
                $reason = "MigrateMembers - email {$user['email']} = $eid, YahooId {$user['yid']} = $yid, YahooUserId {$user['userId']} = $uid";

                if (!$id) {
                    # Unknown user.  Create
                    $id = $u->create(NULL, NULL, $name, 'Migrated from ModTools');
                }

                if ($eid && $id != $eid) {
                    $u->merge($id, $eid, $reason);
                }

                if ($yid && $id != $yid) {
                    $u->merge($id, $yid, $reason);
                }

                if ($uid && $id != $uid) {
                    $u->merge($id, $uid, $reason);
                }

                $u = new User($dbhr, $dbhm, $id);

                $copy = [
                    'userId' => 'yahooUserId',
                    'yid' => 'yahooid'
                ];

                foreach ($copy as $old => $new) {
                    if ($user[$old] && strlen($user[$old]) > 0) {
                        $u->setPrivate($new, $user[$old]);
                    }
                }

                $copy = [
                    'deliveryType' => 'yahooDeliveryType',
                    'postingStatus' => 'yahooPostingStatus'
                ];

                foreach ($copy as $old => $new) {
                    if ($user[$old] && strlen($user[$old]) > 0) {
                        $u->setMembershipAtt($gid, $new, $user[$old]);
                    }
                }

                $emailid = $u->addEmail($user['email'], 1);
                $membs = $u->getMemberships();

                $already = FALSE;
                foreach ($membs as $m) {
                    if ($m['id'] == $gid) {
                        $already = TRUE;
                    }
                }

                if (!$already) {
                    $u->addMembership($gid, User::ROLE_MEMBER, $emailid, MembershipCollection::APPROVED);
                }

                $count++;
                if ($count % 1000 == 0) {
                    error_log("...$count");
                }
            } catch (Exception $e) {
                error_log("Skip MT {$user['uniqueid']} with " . $e->getMessage());
            }
        }
    } else {
        error_log("...not on Iznik");
    }
}
