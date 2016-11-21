<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/mail/Bounce.php');

$to = trim($argv[2]);

$msg = '';

while(!feof(STDIN))
{
    $msg .= fread(STDIN, 1024);
}

$b = new Bounce($dbhr, $dbhm);
$b->save($to, $msg);
