<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=iznik;charset=utf8";
$at = 0;

$groups = $dbhr->preQuery("SELECT id FROM groups WHERE poly IS NOT NULL OR polyofficial IS NOT NULL");
foreach ($groups as $group) {
    error_log($group['id']);
    $gs = $dbhr->preQuery("SELECT GeomFromText(CASE WHEN poly IS NULL THEN polyofficial ELSE poly END) FROM groups WHERE id = ?;", [ $group['id']]);
}
