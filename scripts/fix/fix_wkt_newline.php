<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$groups = $dbhr->preQuery("SELECT * FROM groups WHERE poly IS NOT NULL");

foreach ($groups as $group) {
    if (strpos($group['poly'], "\n") !== FALSE || strpos($group['poly'], "\r") !== FALSE) {
        $poly = str_replace("\n", "", $group['poly']);
        $poly = str_replace("\r", "", $poly);
        $dbhm->preExec("UPDATE groups SET poly = ? WHERE id = ?;", [
            $poly,
            $group['id']
        ]);
        error_log($group['nameshort']. $poly);
    }
}