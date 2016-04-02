<?php
# This is the XMPP pre-bind script, which returns info about the currently logged in user.
session_start();
define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/lib/xmpp-prebind-php-master/lib/XmppPrebind.php');

$sessionInfo = [
    'jid' => NULL,
    'sid' => NULL,
    'rid' => NULL
];

$me = whoAmI($dbhr, $dbhm);

if ($me) {
    # We want to create a resource ID which varies, to allow logins from multiple devices/browsers for the same
    # user.
    $try = 0;
    $sessionInfo = NULL;

    do {
        try {
            $resource = "Iznik-{$_SERVER['HTTP_HOST']}-" . session_id();

            $jid = $me->getJid();
            error_log("Prebind check for $jid");
            $xmppPrebind = new XmppPrebind('iznik', 'http://localhost:5281/http-bind/', $resource, false, false);
            $xmppPrebind->connect($me->getJid(FALSE), $me->getToken());
            error_log("Connected");
            $xmppPrebind->auth();
            error_log("Auth");
            $sessionInfo = $xmppPrebind->getSessionInfo(); // array containing sid, rid and jid
            $sessionInfo['jid'] = $jid;
            error_log("Got info " . var_export($sessionInfo, TRUE));
        } catch (Exception $e) {
            $try ++;
            sleep(1);
        }
    } while ($sessionInfo == NULL && $try < 5);

//    //
//    $jid = $me->getId() . '@iznik';
//    $sid = session_id();
//    $rid = 1;
}

@header('Content-type: application/json');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
@header('Access-Control-Allow-Credentials: true');
@header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

echo json_encode($sessionInfo);
