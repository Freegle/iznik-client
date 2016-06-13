<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/mail/Digest.php');

$groups = $dbhr->preQuery("SELECT id, nameshort FROM groups WHERE `type` = 'Freegle' and nameshort like '%ribble%';");
$d = new Digest($dbhr, $dbhm);

foreach ($groups as $group) {
    $d->send($group['id'], Digest::HOUR1);
}