<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');
require_once(IZNIK_BASE . '/include/config/StdMessage.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

# Zap any existing configs.  The old DB is the master until we migrate.
$dbhm->preExec("DELETE FROM mod_configs;");

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    // PDO::ATTR_PERSISTENT => true, // Persistent connections seem to result in a leak - show status like 'Threads%'; shows an increasing number
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$c = new ModConfig($dbhr, $dbhm);
$u = new User($dbhr, $dbhm);
$g = new Group($dbhr, $dbhm);

$oldconfs = $dbhold->query("SELECT * FROM configs;");
foreach ($oldconfs as $config) {
    # See if we can find the user who created it.
    $sql = "SELECT * FROM moderators WHERE uniqueid = {$config['createdby']};";
    $mods = $dbhold->query($sql);
    foreach ($mods as $mod) {
        $modid = $u->findByEmail($mod['email']);
        error_log("Found modid $modid for {$mod['email']}");

        if (!$modid) {
            error_log("New mod, create user for them");
            $modid = $u->create(NULL, NULL, $mod['name']);
            $u2 = new User($dbhr, $dbhm, $modid);
            $u2->addEmail($mod['email']);
        }

        $cid = $c->create(
            $config['name'],
            $modid
        );

        $c = new ModConfig($dbhr, $dbhm, $cid);
        error_log("...{$config['name']}");

        $atts = array('fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
            'ccfollowupaddr', 'ccrejmembto', 'ccrejmembaddr', 'ccfollmembto', 'ccfollmembaddr', 'protected',
            'messageorder', 'network', 'coloursubj', 'subjreg', 'subjlen');
        foreach ($atts as $att) {
            $c->setPrivate($att, $config[$att]);
        }

        # Migrate messages.
        $sql = "SELECT stdmsg.* FROM stdmsgmap INNER JOIN stdmsg ON stdmsgmap.stdmsgid = stdmsg.uniqueid WHERE stdmsgmap.configid = {$config['uniqueid']};";
        $stdmsgs = $dbhold->query($sql);

        foreach ($stdmsgs as $stdmsg) {
            $s = new StdMessage($dbhr, $dbhm);
            $sid = $s->create($stdmsg['title']);
            $s = new StdMessage($dbhr, $dbhm, $sid);
            $s->setPrivate('configid', $cid);
            $atts = array('action', 'subjpref', 'subjsuff', 'body',
                'rarelyused', 'autosend', 'newmodstatus', 'newdelstatus', 'edittext');

            foreach ($atts as $att) {
                $s->setPrivate($att, $stdmsg[$att]);
            }
        }
    }

    # Migrate which configs are used to moderate.
    $sql = "SELECT groupid, email, name FROM groupsmoderated INNER JOIN moderators ON moderators.uniqueid = groupsmoderated.moderatorid WHERE configid = {$config['uniqueid']};";
    error_log($sql);
    $mods = $dbhold->query($sql);

    foreach ($mods as $mod) {
        $sql = "SELECT * FROM groups WHERE groupid = {$mod['groupid']};";
        $groups = $dbhold->query($sql);

        foreach ($groups as $group) {
            $gid = $g->findByShortName($group['groupname']);
            error_log("Found group id $gid for {$group['groupname']}");
            if ($gid) {
                $modid = $u->findByEmail($mod['email']);

                if (!$modid) {
                    error_log("Don't know {$mod['email']}");
                    # Create a membership for this mod
                    $u2 = new User($dbhr, $dbhm, $modid);
                    $u2->addEmail($mod['email']);
                    $u2->addMembership($group['groupid'], User::ROLE_MODERATOR);
                } else {
                    error_log("Already know {$mod['email']} as $modid");
                    $u2 = new User($dbhr, $dbhm, $modid);
                    if (!$u2->isModOrOwner($gid)) {
                        error_log("But not mod");
                        $u2->addMembership($gid, User::ROLE_MODERATOR);
                    } else {
                        error_log("Already mod or owner");
                    }
                }

                $c->useOnGroup($modid, $gid);
            }
        }
    }

}

