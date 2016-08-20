<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/message/Relevant.php');
global $dbhr, $dbhm;

$rl = new Relevant($dbhr, $dbhm);

$users = $dbhr->preQuery("SELECT id FROM users WHERE lastlocation IS NOT NULL;");
foreach ($users as $user) {
    $rl->sendMessages($user['id']);
}

?>