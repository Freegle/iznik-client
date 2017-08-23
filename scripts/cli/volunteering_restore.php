<?php

# Run on backup server to recover a user from a backup to the live system.  Use with astonishing levels of caution.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Volunteering.php');

$dsn = "mysql:host=localhost;port=3309;dbname=iznik;charset=utf8";
$dbhback = new LoggedPDO($dsn, SQLUSER, SQLPASSWORD, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm volunteering_restore.php -i <id to restore>\n";
} else {
    $id = $opts['i'];

    error_log("Find op $id");
    $vols = $dbhback->preQuery("SELECT * FROM volunteering WHERE id = ?;", [
        $id
    ]);

    foreach ($vols as $vol) {
        error_log("Found it");
        $v = new Volunteering($dbhr, $dbhm);
        $id = $v->create($vol['userid'], $vol['title'], $vol['online'], $vol['location'], $vol['contactname'], $vol['contactphone'], $vol['contactemail'], $vol['contacturl'], $vol['description'], $vol['timecommitment']);
        error_log("...restored as $id");

        $dates = $dbhback->preQuery("SELECT * FROM volunteering_dates WHERE volunteeringid = ?;", [ $vol['id'] ]);

        foreach ($dates as $date) {
            error_log("...restore date {$date['start']} - {$date['end']}, {$date['applyby']}");
            $v->addDate($date['start'], $date['end'], $date['applyby']);
        }

        $groups = $dbhback->preQuery("SELECT * FROM volunteering_groups WHERE volunteeringid = ?;", [ $vol['id'] ]);

        foreach ($groups as $group) {
            error_log("...restore group {$group['groupid']}");
            $v->addGroup($group['groupid']);
        }
    }
}
