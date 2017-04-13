<?php

# Fake user site.
# TODO Messy.
$_SERVER['HTTP_HOST'] = "www.ilovefreegle.org";


require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/user/Story.php');
require_once(IZNIK_BASE . '/include/mail/Newsletter.php');
global $dbhr, $dbhm;

$lockh = lockScript(basename(__FILE__));

$s = new Story($dbhr, $dbhm);
$nid = $s->generateNewsletter();
$n = new Newsletter($dbhr, $dbhm, $nid);

if ($n->getId() == $nid) {
    error_log("Generated newsletter $nid");
    $n->send(NULL, NULL);
}


unlockScript($lockh);