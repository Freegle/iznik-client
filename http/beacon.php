<?php
require_once('/etc/iznik.conf');
require_once(dirname(__FILE__) . '/../include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Alerts.php');
require_once(IZNIK_BASE . '/include/db.php');
global $dbhr, $dbhm;

$data = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
header('Content-Type: image/jpeg');
header('Content-Length: ' . strlen($data));
header('Cache-Control: max-age=315360000');

$id = intval(presdef('id', $_REQUEST, NULL));

if ($id) {
    $a = new Alert($dbhr, $dbhm);
    $a->beacon($id);
}

echo $data;
?>
