<?php
require_once('/etc/iznik.conf');
require_once(dirname(__FILE__) . '/../../include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
global $dbhr, $dbhm;

# Legacy code to redirect old plugin links to new explore links.
$id = presdef('groupid', $_REQUEST);

if ($id) {
    $g = new Group($dbhr, $dbhm, $id);
    $redirect = 'https://' . USER_SITE . "/explore/" . $g->getPrivate('nameshort');
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $redirect");
}
