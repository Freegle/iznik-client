<?php
$scriptstart = microtime(true);

$_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);
$_REQUEST['type'] = $_SERVER['REQUEST_METHOD'];

if (array_key_exists('HTTP_X_HTTP_METHOD_OVERRIDE', $_SERVER)) {
    # Used by Backbone's emulateHTTP to work around servers which don't handle verbs like PATCH very well.
    #
    # We use this because when we issue a PATCH we don't seem to be able to get the body parameters.
    $_REQUEST['type'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    #error_log("Request method override to {$_REQUEST['type']}");
}

require_once('../../include/misc/apiheaders.php');
require_once('../../include/config.php');

# We might profile - only the occasional call as it generates a lot of data.
$xhprof = XHPROF && (mt_rand(0, 1000000) < 1000);

// @codeCoverageIgnoreStart
if ($xhprof) {
    # We are profiling.
    xhprof_enable(XHPROF_FLAGS_CPU);
}

if (file_exists(IZNIK_BASE . '/http/maintenance_on.html')) {
    echo json_encode(array('ret' => 111, 'status' => 'Down for maintenance'));
    exit(0);
}
// @codeCoverageIgnoreEnd

require_once(IZNIK_BASE . '/include/db.php');
global $dbhr, $dbhm;
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/session/Yahoo.php');
require_once(IZNIK_BASE . '/include/session/Facebook.php');
require_once(IZNIK_BASE . '/include/session/Google.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/dashboard/Dashboard.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/message/Item.php');
require_once(IZNIK_BASE . '/include/user/Search.php');
require_once(IZNIK_BASE . '/include/user/Request.php');
require_once(IZNIK_BASE . '/include/user/Story.php');
require_once(IZNIK_BASE . '/include/user/Address.php');
require_once(IZNIK_BASE . '/include/misc/PAF.php');
require_once(IZNIK_BASE . '/include/misc/Donations.php');
require_once(IZNIK_BASE . '/include/misc/Authority.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
require_once(IZNIK_BASE . '/include/user/PushNotifications.php');
require_once(IZNIK_BASE . '/include/user/Schedule.php');
require_once(IZNIK_BASE . '/include/group/Alerts.php');
require_once(IZNIK_BASE . '/include/group/Admin.php');
require_once(IZNIK_BASE . '/include/group/CommunityEvent.php');
require_once(IZNIK_BASE . '/include/group/Volunteering.php');
require_once(IZNIK_BASE . '/include/group/Twitter.php');
require_once(IZNIK_BASE . '/include/group/Facebook.php');
require_once(IZNIK_BASE . '/include/chat/ChatRoom.php');
require_once(IZNIK_BASE . '/include/chat/ChatMessage.php');
require_once(IZNIK_BASE . '/include/misc/Supporters.php');
require_once(IZNIK_BASE . '/include/misc/Polls.php');
require_once(IZNIK_BASE . '/include/mail/MailRouter.php');
require_once(IZNIK_BASE . '/include/misc/plugin.php');
require_once(IZNIK_BASE . '/include/misc/Image.php');
require_once(IZNIK_BASE . '/include/misc/Search.php');
require_once(IZNIK_BASE . '/include/misc/Events.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');
require_once(IZNIK_BASE . '/include/config/StdMessage.php');
require_once(IZNIK_BASE . '/include/config/BulkOp.php');
require_once(IZNIK_BASE . '/include/newsfeed/Newsfeed.php');

# Include each API call
require_once(IZNIK_BASE . '/http/api/abtest.php');
require_once(IZNIK_BASE . '/http/api/authority.php');
require_once(IZNIK_BASE . '/http/api/activity.php');
require_once(IZNIK_BASE . '/http/api/alert.php');
require_once(IZNIK_BASE . '/http/api/admin.php');
require_once(IZNIK_BASE . '/http/api/address.php');
require_once(IZNIK_BASE . '/http/api/changes.php');
require_once(IZNIK_BASE . '/http/api/session.php');
require_once(IZNIK_BASE . '/http/api/modconfig.php');
require_once(IZNIK_BASE . '/http/api/stdmsg.php');
require_once(IZNIK_BASE . '/http/api/bulkop.php');
require_once(IZNIK_BASE . '/http/api/comment.php');
require_once(IZNIK_BASE . '/http/api/dashboard.php');
require_once(IZNIK_BASE . '/http/api/donations.php');
require_once(IZNIK_BASE . '/http/api/error.php');
require_once(IZNIK_BASE . '/http/api/messages.php');
require_once(IZNIK_BASE . '/http/api/message.php');
require_once(IZNIK_BASE . '/http/api/newsfeed.php');
require_once(IZNIK_BASE . '/http/api/invitation.php');
require_once(IZNIK_BASE . '/http/api/item.php');
require_once(IZNIK_BASE . '/http/api/usersearch.php');
require_once(IZNIK_BASE . '/http/api/memberships.php');
require_once(IZNIK_BASE . '/http/api/spammers.php');
require_once(IZNIK_BASE . '/http/api/supporters.php');
require_once(IZNIK_BASE . '/http/api/group.php');
require_once(IZNIK_BASE . '/http/api/groups.php');
require_once(IZNIK_BASE . '/http/api/communityevent.php');
require_once(IZNIK_BASE . '/http/api/plugin.php');
require_once(IZNIK_BASE . '/http/api/user.php');
require_once(IZNIK_BASE . '/http/api/chatrooms.php');
require_once(IZNIK_BASE . '/http/api/chatmessages.php');
require_once(IZNIK_BASE . '/http/api/locations.php');
require_once(IZNIK_BASE . '/http/api/image.php');
require_once(IZNIK_BASE . '/http/api/profile.php');
require_once(IZNIK_BASE . '/http/api/event.php');
require_once(IZNIK_BASE . '/http/api/socialactions.php');
require_once(IZNIK_BASE . '/http/api/poll.php');
require_once(IZNIK_BASE . '/http/api/request.php');
require_once(IZNIK_BASE . '/http/api/schedule.php');
require_once(IZNIK_BASE . '/http/api/stories.php');
require_once(IZNIK_BASE . '/http/api/status.php');
require_once(IZNIK_BASE . '/http/api/volunteering.php');
require_once(IZNIK_BASE . '/http/api/notification.php');
require_once(IZNIK_BASE . '/http/api/mentions.php');
require_once(IZNIK_BASE . '/http/api/logs.php');

use GeoIp2\Database\Reader;

$includetime = microtime(true) - $scriptstart;

# All API calls come through here.
#error_log("Request " . var_export($_REQUEST, TRUE));
#error_log("Server " . var_export($_SERVER, TRUE));

if (array_key_exists('model', $_REQUEST)) {
    # Used by Backbone's emulateJSON to work around servers which don't handle requests encoded as
    # application/json.
    $_REQUEST = array_merge($_REQUEST, json_decode($_REQUEST['model'], true));
    unset($_REQUEST['model']);
}

$call = pres('call', $_REQUEST);

if ($_REQUEST['type'] == 'OPTIONS') {
    # We don't bother returning different values for different calls.
    http_response_code(204);
    @header('Allow: POST, GET, DELETE, PUT');
    @header('Access-Control-Allow-Methods:  POST, GET, DELETE, PUT');
} else {
    # Actual API calls
    $ret = array('ret' => 1000, 'status' => 'Invalid API call');
    $t = microtime(true);

    # We wrap the whole request in a retry handler.  This is so that we can deal with errors caused by
    # conflicts within the Percona cluster.
    $apicallretries = 0;

    # This is an optimisation for User.php.
    $_SESSION['modorowner'] = presdef('modorowner', $_SESSION, []);

    # Update our last access time for this user.  We do this every 60 seconds.  This is used to return our
    # roster status in ChatRoom.php, and also for spotting idle members.
    $id = pres('id', $_SESSION);
    $last = presdef('lastaccessupdate', $_SESSION, 0);
    if ($id && (time() - $last > 60)) {
        $dbhm->background("UPDATE users SET lastaccess = NOW() WHERE id = $id;");
        $_SESSION['lastaccessupdate'] = time();
    }

    do {
        if ($_REQUEST['type'] != 'GET') {
            # Check that we're not posting from a blocked country.
            try {
                $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
                $ip = $_SERVER['REMOTE_ADDR'];
                $record = $reader->country($ip);
                $country = $record->country->name;
                # Failed to look it up.
                $countries = $dbhr->preQuery("SELECT * FROM spam_countries WHERE country = ?;", [$country]);
                foreach ($countries as $country) {
                    error_log("Block post from {$country['country']} " . var_export($_REQUEST, TRUE));
                    echo json_encode(array('ret' => 0, 'status' => 'Success'));
                    break 2;
                }
            } catch (Exception $e) {
            }
        }

        # Duplicate POST protection
        if ((DUPLICATE_POST_PROTECTION > 0) && array_key_exists('REQUEST_METHOD', $_SERVER) && ($_REQUEST['type'] == 'POST')) {
            # We want to make sure that we don't get duplicate POST requests within the same session.  We can't do this
            # using information stored in the session because when Redis is used as the session handler, there is
            # no session locking, and therefore two requests in quick succession could be allowed.  So instead
            # we use Redis directly with a roll-your-own mutex.
            #
            # TODO uniqid() is not actually unique.  Nor is md5.
            $req = $_SERVER['REQUEST_URI'] . serialize($_REQUEST);
            $lockkey = 'POST_LOCK_' . session_id();
            $datakey = 'POST_DATA_' . session_id();
            $uid = uniqid('', TRUE);
            $predis = new Redis();
            $predis->pconnect(REDIS_CONNECT);

            # Get a lock.
            $start = time();
            do {
                $rc = $predis->setNx($lockkey, $uid);

                if ($rc) {
                    # We managed to set it.  Ideally we would set an expiry time to make sure that if we got
                    # killed right now, this session wouldn't hang.  But that's an extra round trip on each
                    # API call, and the worst case would be a single session hanging, which we can live with.

                    # Sound out the last POST.
                    $last = $predis->get($datakey);

                    # Some actions are ok, so we exclude those.
                    if (!in_array($call, [ 'session', 'correlate', 'chatrooms', 'events', 'upload']) &&
                        $last === $req) {
                        # The last POST request was the same.  So this is a duplicate.
                        $predis->del($lockkey);
                        $ret = array('ret' => 999, 'text' => 'Duplicate request - rejected.', 'data' => $_REQUEST);
                        echo json_encode($ret);
                        break 2;
                    }

                    # The last request wasn't the same.  Save this one.
                    $predis->set($datakey, $req);
                    $predis->expire($datakey, DUPLICATE_POST_PROTECTION);

                    # We're good to go - release the lock.
                    $predis->del($lockkey);
                    break;
                    // @codeCoverageIgnoreStart
                } else {
                    # We didn't get the lock - another request for this session must have it.
                    usleep(100000);
                }
            } while (time() < $start + 45);
            // @codeCoverageIgnoreEnd
        }

        try {
            # Each call is inside a file with a suitable name.
            #
            # call_user_func doesn't scale well on multicores with HHVM, so we need can't figure out the function from
            # the call name - use a switch instead.
            switch ($call) {
                case 'abtest':
                    $ret = abtest();
                    break;
                case 'activity':
                    $ret = activity();
                    break;
                case 'authority':
                    $ret = authority();
                    break;
                case 'address':
                    $ret = address();
                    break;
                case 'alert':
                    $ret = alert();
                    break;
                case 'admin':
                    $ret = admin();
                    break;
                case 'changes':
                    $ret = changes();
                    break;
                case 'dashboard':
                    $ret = dashboard();
                    break;
                case 'error':
                    $ret = error();
                    break;
                case 'exception':
                    # For UT
                    throw new Exception();
                case 'image':
                    $ret = image();
                    break;
                case 'profile':
                    $ret = profile();
                    break;
                case 'event':
                    $ret = event();
                    break;
                case 'socialactions':
                    $ret = socialactions();
                    break;
                case 'messages':
                    $ret = messages();
                    break;
                case 'message':
                    $ret = message();
                    break;
                case 'invitation':
                    $ret = invitation();
                    break;
                case 'item':
                    $ret = item();
                    break;
                case 'usersearch':
                    $ret = usersearch();
                    break;
                case 'memberships':
                    $ret = memberships();
                    break;
                case 'spammers':
                    $ret = spammers();
                    break;
                case 'plugin':
                    $ret = plugin();
                    break;
                case 'session':
                    $ret = session();
                    break;
                case 'supporters':
                    $ret = supporters();
                    break;
                case 'group':
                    $ret = group();
                    break;
                case 'groups':
                    $ret = groups();
                    break;
                case 'communityevent':
                    $ret = communityevent();
                    break;
                case 'locations':
                    $ret = locations();
                    break;
                case 'modconfig':
                    $ret = modconfig();
                    break;
                case 'stdmsg':
                    $ret = stdmsg();
                    break;
                case 'bulkop':
                    $ret = bulkop();
                    break;
                case 'comment':
                    $ret = comment();
                    break;
                case 'user':
                    $ret = user();
                    break;
                case 'chatrooms':
                    $ret = chatrooms();
                    break;
                case 'chatmessages':
                    $ret = chatmessages();
                    break;
                case 'poll':
                    $ret = poll();
                    break;
                case 'request':
                    $ret = request();
                    break;
                case 'schedule':
                    $ret = schedule();
                    break;
                case 'stories':
                    $ret = stories();
                    break;
                case 'donations':
                    $ret = donations();
                    break;
                case 'status':
                    $ret = status();
                    break;
                case 'volunteering':
                    $ret = volunteering();
                    break;
                case 'logs':
                    $ret = logs();
                    break;
                case 'newsfeed':
                    $ret = newsfeed();
                    break;
                case 'notification':
                    $ret = notification();
                    break;
                case 'mentions':
                    $ret = mentions();
                    break;
                case 'echo':
                    $ret = array_merge($_REQUEST, $_SERVER);
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
                case 'DBleaveTrans':
                    # For UT
                    $dbhm->beginTransaction();

                    break;
            }

            # If we get here, everything worked.
            if ($call == 'upload') {
                # Output is handled within the lib.
            } else if (pres('img', $ret)) {
                # This is an image we want to output.  Can cache forever - if an image changes it would get a new id
                @header('Content-Type: image/jpeg');
                @header('Content-Length: ' . strlen($ret['img']));
                @header('Cache-Control: max-age=5360000');
                print $ret['img'];
            } else {
                # This is a normal API call.  Add profiling info.
                $ret['call'] = $call;
                $ret['type'] = $_REQUEST['type'];
                $ret['session'] = session_id();
                $ret['duration'] = (microtime(true) - $scriptstart);
                $ret['cpucost'] = getCpuUsage();
                $ret['dbwaittime'] = $dbhr->getWaitTime() + $dbhm->getWaitTime();
                $ret['includetime'] = $includetime;
                $ret['cachetime'] = $dbhr->getCacheTime();
                $ret['cachequeries'] = $dbhr->getCacheQueries();
                $ret['cachehits'] = $dbhr->getCacheHits();

                filterResult($ret);
                $str = json_encode($ret);
                echo $str;
            }

            if ($apicallretries > 0) {
                error_log("API call $call worked after $apicallretries");
            }

            $ip = presdef('REMOTE_ADDR', $_SERVER, '');

            if (BROWSERTRACKING && (presdef('type', $_REQUEST, NULL) != 'GET') &&
                (gettype($ret) == 'array' && !array_key_exists('nolog', $ret))) {
                # Save off the API call and result, except for the (very frequent) event tracking calls.  Don't
                # save GET calls as they don't change the DB and there are a lot of them.
                #
                # Beanstalk has a limit on the size of job that it accepts; no point trying to log absurdly large
                # API requests.
                $req = json_encode($_REQUEST);
                $rsp = json_encode($ret);

                if (strlen($req) + strlen($rsp) > 180000) {
                    $req = substr($req, 0, 1000);
                    $rsp = substr($rsp, 0, 1000);
                }

                $sql = "INSERT INTO logs_api (`userid`, `ip`, `session`, `request`, `response`) VALUES (" . presdef('id', $_SESSION, 'NULL') . ", '" . presdef('REMOTE_ADDR', $_SERVER, '') . "', " . $dbhr->quote(session_id()) .
                    ", " . $dbhr->quote($req) . ", " . $dbhr->quote($rsp) . ");";
                $dbhm->background($sql);
            }

            break;
        } catch (Exception $e) {
            # This is our retry handler - see apiheaders.
            if ($e instanceof DBException) {
                # This is a DBException.  We want to retry, which means we just go round the loop
                # again.
                error_log("DB Exception try $apicallretries," . $e->getMessage() . ", " . $e->getTraceAsString());
                $apicallretries++;

                if ($apicallretries >= API_RETRIES) {
                    echo json_encode(array('ret' => 997, 'status' => 'DB operation failed after retry', 'exception' => $e->getMessage()));
                }
            } else {
                # Something else.
                error_log("Uncaught exception at " . $e->getFile() . " line " . $e->getLine() . " " . $e->getMessage());
                echo json_encode(array('ret' => 998, 'status' => 'Unexpected error', 'exception' => $e->getMessage()));
                break;
            }

            # Make sure the duplicate POST detection doesn't throw us.
            $_REQUEST['retry'] = uniqid('', TRUE);
        }
    } while ($apicallretries < API_RETRIES);

    # Any outstanding transaction is a bug; force a rollback to avoid locks lasting beyond this call.
    if ($dbhm->inTransaction()) {
        $dbhm->rollBack();
    }

    if ($_REQUEST['type'] != 'GET') {
        # This might have changed things.
        $_SESSION['modorowner'] = [];
    }
}

// @codeCoverageIgnoreStart
if ($xhprof) {
    # We collect the stats and aggregate the data into the DB
    $stats = xhprof_disable();

    foreach ($stats as $edge => $data) {
        $p = strpos($edge, '==>');
        if ($p !== FALSE) {
            $caller = substr($edge, 0, $p);
            $callee = substr($edge, $p + 3);
            $data['caller'] = $caller;
            $data['callee'] = $callee;

            $atts = [ 'ct', 'wt', 'cpu', 'mu', 'pmu', 'alloc', 'free'];
            $sql = "INSERT INTO logs_profile (caller, callee";

            foreach ($atts as $att) {
                if (pres($att, $data)) {
                    $sql .= ", $att";
                }
            };

            $sql .= ") VALUES (" . $dbhr->quote($caller) . ", " . $dbhr->quote($callee);

            foreach ($atts as $att) {
                if (pres($att, $data)) {
                    $sql .= ", {$data[$att]}";
                }
            }

            $sql .= ") ON DUPLICATE KEY UPDATE ";

            foreach ($atts as $att) {
                if (pres($att, $data)) {
                    $sql .= "$att = $att + {$data[$att]}, ";
                }
            }

            $sql = substr($sql, 0, strlen($sql) - 2) . ";";
            $dbhm->background($sql);
        }
    }
}
// @codeCoverageIgnoreEnd

