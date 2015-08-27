<?php

require_once dirname(__FILE__) . '/../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/mail/MailRouter.php');

$m = new MailRouter($dbhr, $dbhm);
$m->routeAll();

