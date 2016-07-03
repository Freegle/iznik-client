<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbh = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$c = new CommunityEvent($dbhr, $dbhm);
$u = new User($dbhr, $dbhm);
$g = new Group($dbhr, $dbhm);

$events = $dbh->query("SELECT events.*, facebook.email, groups.groupname FROM events LEFT JOIN facebook ON events.from = facebook.facebookid INNER JOIN groups ON groups.groupid = events.groupid;");

foreach ($events as $event) {
    $existing = $dbhr->preQuery("SELECT id FROM communityevents WHERE legacyid = ?;", [ $event['uniqueevent']]);

    if (count($existing) == 0) {
        # Not got this one.
        $uid = $u->findByEmail($event['email']);
        error_log("#{$event['uniqueevent']} user $uid {$event['title']}");
        $id = $c->create($uid, $event['title'], $event['location'], $event['contactname'], $event['contactphone'], $event['contactemail'], $event['description']);
        $c->setPrivate('pending', 0);
        $c->setPrivate('legacyid', $event['uniqueevent']);

        $gid = $g->findByShortName($event['groupname']);
        $c->addGroup($gid);

        $start = date("Y-m-d H:i:s", strtotime($event['start'] . ' UTC'));
        $end = date("Y-m-d H:i:s", strtotime($event['end'] . ' UTC'));
        $c->addDate($start, $end);
    }
}

$dbhm->preExec("UPDATE communityevents SET contactemail = NULL WHERE contactemail = '';");
$dbhm->preExec("UPDATE communityevents SET contactphone = NULL WHERE contactphone = '';");
$dbhm->preExec("UPDATE communityevents SET contactname = NULL WHERE contactname = '';");
