<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$u = new User($dbhr, $dbhm);
$s = new Spam($dbhr, $dbhm);

$dbhm->preExec("DELETE FROM spam_users;");

$spammers = $dbhold->query("SELECT spammerlist.*, moderators.email AS modemail FROM spammerlist LEFT JOIN moderators ON spammerlist.modid = moderators.uniqueid;");
foreach ($spammers as $spammer) {
    $spammer['email'] = trim($spammer['email']);
    $spammer['modemail'] = trim($spammer['modemail']);

    # See if we know this user.
    $id = $u->findByEmail($spammer['email']);

    # See if we know the reporting mod.
    $modid = $u->findByEmail($spammer['modemail']);

    if (!$id) {
        $id = $u->create(NULL, NULL, NULL);
        #error_log("Not known, created $id");
        $u->addEmail($spammer['email']);
    }

    $sid = $s->addSpammer($id, Spam::TYPE_SPAMMER, $spammer['reason']);
    $sql = "UPDATE spam_users SET added = ?, byuserid = ? WHERE id = ?";
    $dbhm->preExec($sql, [ $spammer['added'], $modid, $sid]);
}

