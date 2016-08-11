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

$dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

error_log("Migrate FD memberships");
$groups = $dbhfd->query("SELECT * FROM groups WHERE grouppublish = 1 ORDER BY LOWER(groupname) ASC;");
$groupcount = 0;

foreach ($groups as $group) {
    $dbhfd = new PDO($dsnfd, $dbconfig['user'], $dbconfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => FALSE
    ));

    $groupcount++;
    error_log("Migrate settings for {$group['groupname']}");
    $gid = $g->findByShortName($group['groupname']);

    if ($gid) {
        $g = new Group($dbhr, $dbhm, $gid);

        $users = $dbhfd->query("SELECT * FROM users WHERE groupid = {$group['groupid']} AND deletedfromyahoo = 0;");
        $count = 0;
        foreach ($users as $user) {
            try {
                $uid = $u->findByEmail($user['useremail']);
                $u = new User($dbhr, $dbhm, $uid);

                $dig = $user['digest'] ? $user['maxdigestdelay'] : 0;
                $current = $u->getMembershipAtt($gid, 'emailfrequency');

                if ($current != $dig) {
                    error_log("  {$user['useremail']} on {$group['groupname']} email settings from $current to $dig");
                    $u->setMembershipAtt($gid, 'emailfrequency', $dig);
                }

                $events = $user['eventsdisabled'] ? 0 : 1;
                $current = $u->getMembershipAtt($gid, 'eventsallowed');

                if ($current != $events) {
                    error_log("  {$user['useremail']} on {$group['groupname']} event settings from $current to $events");
                    $u->setMembershipAtt($gid, 'eventsallowed', $events);
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
