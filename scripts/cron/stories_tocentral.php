<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "www.ilovefreegle.org";

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/user/Story.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$s = new Story($dbhr, $dbhm);
$count = $s->sendToCentral();
error_log("Sent $count stories");
unlockScript($lockh);