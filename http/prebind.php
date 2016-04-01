<?php
# This is the XMPP pre-bind script, which returns info about the currently logged in user.
session_start();
define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/session/Session.php');

$jid = $sid = $rid = NULL;

$me = whoAmI($dbhr, $dbhm);

if ($me) {
    $jid = $me->getId() . '@iznik';
    $sid = session_id();
    $rid = 1;
}

@header('Content-type: application/json');

echo json_encode([
    'jid' => $jid,
    'sid' => $sid,
    'rid' => $rid
]);
