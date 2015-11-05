<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');

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

$oldconfs = $dbhold->query("SELECT * FROM configs;");
foreach ($oldconfs as $config) {
    # See if we can find the user who created it.
    $sql = "SELECT * FROM moderators WHERE uniqueid = {$config['createdby']};";
    $mods = $dbhold->query($sql);
    foreach ($mods as $mod) {
        $modid = $u->findByEmail($mod['email']);
        error_log("Found modid $modid for {$mod['email']}");

        if ($modid) {
            $c->create(
                $config['name'],
                $modid
            );
            error_log("...{$config['name']}");

            $atts = array('fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
                'ccfollowupaddr', 'ccrejmembto', 'ccrejmembaddr', 'ccfollmembto', 'ccfollmembaddr', 'protected',
                'messageorder', 'network', 'coloursubj', 'subjreg', 'subjlen');
            foreach ($atts as $att) {
                $c->setPrivate($att, $config[$att]);
            }
        }
    }
}

