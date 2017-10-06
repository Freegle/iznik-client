<?php
define('SQLLOG', FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

$auths = $dbhr->preQuery("SELECT id, name FROM authorities ORDER BY id;");
$dbhm->preExec("UPDATE authorities SET simplified = polygon");

foreach ($auths as $auth) {
    try {
        $simps = $dbhr->preQuery("SELECT AsText(ST_simplify(polygon, 0.001)) AS simp FROM authorities WHERE id = ?;", [
            $auth['id']
        ]);

        foreach ($simps as $simp) {
            $dbhm->preExec("UPDATE authorities SET simplified = GeomFromText(?) WHERE id = ?;", [ $simp['simp'], $auth['id'] ]);
        }
    } catch (Exception $e) {
        error_log("{$auth['id']} {$auth['name']} failed");
    }
}