<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = Group::get($dbhr, $dbhm);

$mods = $dbhold->query("SELECT groups.groupname, moderators.email, moderators.name, moderators.yahooid,
 groupsmoderated.showinallmessages, groupsmoderated.showinallmembers
FROM groups
INNER JOIN groupsmoderated ON groups.groupid = groupsmoderated.groupid
INNER JOIN moderators ON moderators.uniqueid = groupsmoderated.moderatorid;");

foreach ($mods as $mod) {
    try {
        $g = Group::get($dbhr, $dbhm);
        $gid = $g->findByShortName($mod['groupname']);

        if ($gid) {
            $u = User::get($dbhr, $dbhm);
            $uid1 = $u->findByEmail($mod['email']);
            $uid2 = $u->findByYahooId($mod['yahooid']);

            if ($uid1 && $uid2 && $uid1 != $uid2) {
                $u->merge($uid1, $uid2, "MigrateMods - {$mod['email']} = $uid1, {$mod['yahooid']} = $uid2");
            }

            $uid = $uid1;

            if (!$uid) {
                $uid = $u->create(NULL, NULL, $mod['name'], "Migrated from ModTools Moderators");
            }

            if ($uid) {
                error_log("Found group $gid mod $uid {$mod['yahooid']}");
                $u = User::get($dbhr, $dbhm, $uid);
                $u->addLogin('Yahoo', $mod['yahooid']);
                $emailid = $u->addEmail($mod['email'], 1);

                if ($emailid) {
                    error_log("Set user $uid to have Yahoo id {$mod['yahooid']}");
                    $u->setPrivate('yahooid', $mod['yahooid']);

                    # Assume we're at least a mod - old ModTools doesn't know if we're an owner.
                    $u->addMembership($gid, User::ROLE_MODERATOR, $emailid);

                    $u->setGroupSettings($gid, [
                        'showmessages' => intval($mod['showinallmessages']),
                        'showmembers' => intval($mod['showinallmembers'])
                    ]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Skip {$mod['yahooid']} as " . $e->getMessage());
    }
}

