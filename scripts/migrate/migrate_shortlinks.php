<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsni = "mysql:host={$dbconfig['host']};dbname=ilovefreegle;charset=utf8";

$dbhi = new PDO($dsni, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$shortlinks = $dbhi->query("SELECT * FROM perch_shortlinks;");

$dbhm->preExec("TRUNCATE shortlinks");

foreach ($shortlinks as $s) {
    error_log("{$s['shortlinkID']} {$s['keyword']} {$s['typeID']}");

    if  ($s['typeID'] == 2) {
        # Group.
        $groups = $dbhi->query("SELECT * FROM perch_groups WHERE groupID = {$s['groupID']};");
        foreach ($groups as $group) {
            error_log("Group link");
            $nameshort = substr($group['groupURL'], strrpos($group['groupURL'], '/') + 1);
            $g = new Group($dbhr, $dbhm);
            $gid = $g->findByShortName($nameshort);

            if ($gid) {
                error_log("Found");
                $dbhm->preExec("INSERT INTO shortlinks (name, type, groupid) VALUES (?,?,?);", [
                    $s['keyword'],
                    'Group',
                    $gid
                ]);
            }
        }
    } else {
        $dbhm->preExec("INSERT INTO shortlinks (name, type, url) VALUES (?,?,?);", [
            $s['keyword'],
            'Other',
            $s['shortlinkURL']
        ]);
    }
}
