<?php
$scriptstart = microtime(true);

require_once('../../include/misc/apiheaders.php');
require_once('../../include/config.php');
require_once(IZNIK_BASE . '/include/db.php');
global $dbhr, $dbhm;
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/session/Yahoo.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/dashboard/Dashboard.php');
require_once(IZNIK_BASE . '/include/message/Collection.php');
require_once(IZNIK_BASE . '/include/misc/Supporters.php');

# Include each API call
require_once(IZNIK_BASE . '/http/api/session.php');
require_once(IZNIK_BASE . '/http/api/dashboard.php');
require_once(IZNIK_BASE . '/http/api/messages.php');
require_once(IZNIK_BASE . '/http/api/message.php');
require_once(IZNIK_BASE . '/http/api/correlate.php');
require_once(IZNIK_BASE . '/http/api/supporters.php');

$includetime = microtime(true) - $scriptstart;

# All API calls come through here.
$call = pres('call', $_REQUEST);
$_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);
$_REQUEST['type'] = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    # We don't bother returning different values for different calls.
    http_response_code(204);
    @header('Allow: POST, GET, DELETE, PUT');
    @header('Access-Control-Allow-Methods:  POST, GET, DELETE, PUT');
} else {
    # Actual API calls
    $ret = array('ret' => 1000, 'status' => 'Invalid API call');
    $t = microtime(true);
    $whoamitime = microtime(true) - $t;

    # We wrap the whole request in a retry handler.  This is so that we can deal with errors caused by
    # conflicts within the Percona cluster.
    $apicallretries = 0;

    do {
        # Duplicate POST protection
        if ((DUPLICATE_POST_PROTECTION > 0) && array_key_exists('REQUEST_METHOD', $_SERVER) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
            $req = $_SERVER['REQUEST_URI'] . serialize($_REQUEST);

            # Repeat logins are OK.
            #
            # So are correlations, which are repeatable without ill effects.
            if (($call != 'session') && ($call != 'correlate') &&
                array_key_exists('POSTLASTTIME', $_SESSION)) {
                $ago = time() - $_SESSION['POSTLASTTIME'];

                if (($ago < DUPLICATE_POST_PROTECTION) && ($req == $_SESSION['POSTLASTDATA'])) {
                    $ret = array('ret' => 999, 'text' => 'Duplicate request - rejected.', 'data' => $_REQUEST);
                    echo json_encode($ret);
                    break;
                }
            }

            $_SESSION['POSTLASTTIME'] = time();
            $_SESSION['POSTLASTDATA'] = $req;
        }

        try {
            # Each call is inside a file with a suitable name.
            #
            # call_user_func doesn't scale well on multicores with HHVM, so we need can't figure out the function from
            # the call name - use a switch instead.
            switch ($call) {
                case 'dashboard':
                    $ret = dashboard();
                    break;
                case 'exception':
                    # For UT
                    throw new Exception();
                case 'messages':
                    $ret = messages();
                    break;
                case 'message':
                    $ret = message();
                    break;
                case 'correlate':
                    $ret = correlate();
                    break;
                case 'session':
                    $ret = session();
                    break;
                case 'supporters':
                    $ret = supporters();
                    break;
                case 'DBexceptionWork':
                    # For UT
                    if ($apicallretries < 2) {
                        error_log("Fail DBException $apicallretries");
                        throw new DBException();
                    }

                    break;
                case 'DBexceptionFail':
                    # For UT
                    throw new DBException();
            }

            # If we get here, everything worked.  Add profiling info.
            $ret['call'] = $call;
            $ret['type'] = $_SERVER['REQUEST_METHOD'];
            $ret['session'] = session_id();
            $ret['duration'] = (microtime(true) - $scriptstart);
            $ret['cpucost'] = getCpuUsage();
            $ret['dbwaittime'] = $dbhr->getWaitTime() + $dbhm->getWaitTime();
            $ret['includetime'] = $includetime;
            $ret['whoamitime'] = $whoamitime;

            filterResult($ret);

            echo json_encode($ret);

            if ($apicallretries > 0) {
                error_log("API call $call worked after $apicallretries");
            }

            if (BROWSERTRACKING && ($call != 'event_save')) {
                # Save off the API call and result, except for the (very frequent) event tracking calls.
                $sql = "INSERT INTO logs_api (`session`, `request`, `response`) VALUES (" . $dbhr->quote(session_id()) . ", " . $dbhr->quote(json_encode($_REQUEST)) . ", " . $dbhr->quote(json_encode($ret)) . ");";
                $dbhm->background($sql);
            }

            break;
        } catch (Exception $e) {
            # This is our retry handler - see apiheaders.
            if ($e instanceof DBException) {
                # This is a DBException.  We want to retry, which means we just go round the loop
                # again.
                error_log("DB Exception try $apicallretries ");
                $apicallretries++;

                if ($apicallretries >= API_RETRIES) {
                    echo json_encode(array('ret' => 997, 'status' => 'DB operation failed after retry', 'exception' => $e->getMessage()));
                }
            } else {
                # Something else.
                error_log("Uncaught exception " . $e->getMessage());
                echo json_encode(array('ret' => 998, 'status' => 'Unexpected error', 'exception' => $e->getMessage()));
                break;
            }

            # Make sure the duplicate POST detection doesn't throw us.
            unset($_SESSION['POSTLASTTIME']);
        }
    } while ($apicallretries < API_RETRIES);
}
