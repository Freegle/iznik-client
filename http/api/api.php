<?php
$scriptstart = microtime(true);

require_once('../../include/misc/apiheaders.php');
require_once('../../include/config.php');
require_once(IZNIK_BASE . '/include/db.php');
global $dbhr, $dbhm;
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/utils.php');

# Include each API call
require_once(IZNIK_BASE . '/http/api/session_get.php');

$includetime = microtime(true) - $scriptstart;

# All API calls come through here.
$call = pres('call', $_REQUEST);

$ret = array('ret' => 1000, 'status' => 'No return code defined');
$t = microtime(true);
$me = whoAmI($dbhr, $dbhm, true);
$whoamitime = microtime(true) - $t;

# We wrap the whole request in a retry handler.  This is so that we can deal with errors caused by
# conflicts within the Percona cluster.
$apicallretries = 0;

do {
    # Duplicate POST protection
    if ((DUPLICATE_POST_PROTECTION > 0) && array_key_exists('REQUEST_METHOD', $_SERVER) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
        $req = $_SERVER['REQUEST_URI'] . serialize($_REQUEST);

        # Repeat logins are OK.
        if (($_SERVER['REQUEST_URI'] != '/api/session_login.php') &&
            array_key_exists('POSTLASTTIME', $_SESSION)) {
            $ago = time() - $_SESSION['POSTLASTTIME'];

            if (($ago < DUPLICATE_POST_PROTECTION) && ($req == $_SESSION['POSTLASTDATA'])) {
                $ret = array('ret' => 999, 'text' => 'Duplicate request - rejected.', 'data' => $_REQUEST);
                echo json_encode($ret);
                exit(0);
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
            case 'session_get':
                $ret = session_get();
                break;
        }

        # If we get here, everything worked.  Add profiling info.
        $ret['call'] = $call;
        $ret['scriptduration'] = (microtime(true) - $scriptstart);
        $ret['cpucost'] = getCpuUsage();
        $ret['dbwaittime'] = $dbhr->getWaitTime() + $dbhm->getWaitTime();
        $ret['includetime'] = $includetime;
        $ret['whoamitime'] = $whoamitime;

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
        } else {
            # Something else.
            error_log("Uncaught exception " . $e->getMessage());
            echo json_encode(array('ret' => 999, 'status' => 'Unexpected error', 'exception' => $e->getMessage()));
            exit(0);
        }
    }
} while ($apicallretries < API_RETRIES);
