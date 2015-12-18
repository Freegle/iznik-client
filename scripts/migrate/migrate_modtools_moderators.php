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

$g = new Group($dbhr, $dbhm);

$mods = $dbhold->query("SELECT groups.groupname, moderators.email, moderators.name, moderators.yahooid,
 groupsmoderated.showinallmessages, groupsmoderated.showinallmembers
FROM groups
INNER JOIN groupsmoderated ON groups.groupid = groupsmoderated.groupid
INNER JOIN moderators ON moderators.uniqueid = groupsmoderated.moderatorid;");

foreach ($mods as $mod) {
    $g = new Group($dbhr, $dbhm);
    $gid = $g->findByShortName($mod['groupname']);

    if ($gid) {
        $u = new User($dbhr, $dbhm);
        $uid = $u->findByEmail($mod['email']);

        if (!$uid) {
            $uid = $u->create(NULL, NULL, $mod['name']);
        }

        if ($uid) {
            error_log("Found group $gid mod $uid {$mod['yahooid']}");
            $u = new User($dbhr, $dbhm, $uid);
            $u->addLogin('Yahoo', $mod['yahooid']);
            $u->addEmail($mod['email'], 1);

            # Assume we're at least a mod - old ModTools doesn't know if we're an owner.
            $u->addMembership($gid, User::ROLE_MODERATOR);

            $u->setGroupSettings($gid, [
                'showmessages' => intval($mod['showinallmessages']),
                'showmembers' => intval($mod['showinallmembers'])
            ]);
        }
    }
}

