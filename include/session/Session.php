<?php

require_once(IZNIK_BASE . '/include/utils.php');

# You'll notice some complex stuff in here to do with sessions.  By default in PHP you use session_start, but this is
# quite expensive, so we only want to call it from within whoAmI() when we know we need it.  Again by default
# once you've called it, the session is then blocked, which is bad for simultaneous AJAX calls, so we unlock write access
# unless told to keep it.  We also have a call to reclaim write access to the session for some cases.

if (pres('api_key', $_REQUEST)) {
    # We have been passed a session id.
    #
    # One example of this is when we are called from Swagger.
    session_id($_REQUEST['api_key']);
}

if (!isset($_SESSION)) {
    session_start();
}

function prepareSession($dbhr, $dbhm) {
    if (!pres('id', $_SESSION)) {
        $userid = NULL;

        if (array_key_exists(COOKIE_NAME, $_COOKIE)) {
            # Check our cookie to see if it's a valid session
            $cookie = json_decode($_COOKIE[COOKIE_NAME], true);
            #error_log("Cookie " . var_export($cookie, TRUE));

            if ((array_key_exists('id', $cookie)) &&
                (array_key_exists('series', $cookie)) &&
                (array_key_exists('token', $cookie))
            ) {
                $s = new Session($dbhr, $dbhm);
                $userid = $s->verify($cookie['id'], $cookie['series'], $cookie['token']);
            }
        }

        if (!$userid) {
            # We might not have a cookie, but we might have push credentials.  This happens when we are logged out
            # on the client but get a notification.  That is sufficient to log us in.
            $pushcreds = presdef('pushcreds', $_REQUEST, NULL);
            error_log("No session, pushcreds $pushcreds " . var_exporT($_REQUEST, TRUE));
            if ($pushcreds) {
                $sql = "SELECT * FROM users_push_notifications WHERE subscription = ?;";
                $pushes = $dbhr->preQuery($sql, [$pushcreds]);
                foreach ($pushes as $push) {
                    $s = new Session($dbhr, $dbhm);
                    error_log("Log in as {$push['userid']}");
                    $s->create($push['userid']);
                }
            }
        }
    }
}

function partner($dbhr, $key) {
    $ret = FALSE;
    $partners = $dbhr->preQuery("SELECT * FROM partners_keys WHERE `key` = ?;", [ $key ]);
    foreach ($partners as $partner) {
        $ret = TRUE;
    }

    return($ret);
}

function whoAmI(LoggedPDO $dbhr, $dbhm, $writeaccess = false)
{
    prepareSession($dbhr, $dbhm);

    # Tempting to cache user info in the session, but we would then have to
    # update or invalidate it when anything changes.  That is a fertile source
    # of bugs.
    $id = pres('id', $_SESSION);
    $ret = NULL;

    if ($id) {
        # We are logged in.  Get our details
        $ret = new User($dbhr, $dbhm, $id);
    }

    if (!$writeaccess) {
        # Release write access on the session to allow multiple AJAX requests to
        # complete in parallel.
        session_write_close();
    }

    return($ret);
}

# Backbone may send requests wrapped insode a model; extract them.
if (array_key_exists ( 'model', $_REQUEST )) {
    $_REQUEST = array_merge ( $_REQUEST, json_decode ( $_REQUEST ['model'], true ) );
}

class Session {
    # Based on http://stackoverflow.com/questions/244882/what-is-the-best-way-to-implement-remember-me-for-a-website
    private $dbhr;
    private $dbhm;
    private $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUserId() {
        $sql = "SELECT userid FROM sessions WHERE id = ?;";
        $sessions = $this->dbhr->preQuery($sql, [ $this->id ]);
        $ret = NULL;
        foreach ($sessions as $session) {
            $ret = $session['userid'];
        }

        return($ret);
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function create($userid) {
        # Destroy any existing sessions.
        $this->destroy($userid);

        # Generate a new series and token.
        $series = devurandom_rand();
        $token  = devurandom_rand();
        $thash  = sha1($token);

        $sql = "REPLACE INTO sessions (`userid`, `series`, `token`) VALUES (?,?,?);";
        $this->dbhm->preExec($sql, [
            $userid,
            $series,
            $thash
        ]);

        $id = $this->dbhm->lastInsertId();
        $this->id = $id;

        #error_log("Logged in as $userid");
        $_SESSION['id'] = $userid;
        $_SESSION['logged_in'] = TRUE;

        $ss = array(
            'id' => $id,
            'series' => $series,
            'token' => $thash
        );

        #error_log("Create cookie " . json_encode($ss));
        # Set the cookie which means the client will remember and use this.
        @setcookie(COOKIE_NAME, json_encode($ss), time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST'],
            false, true);

        return ($ss);
    }

    public function verify($id, $series, $token) {
        # Look for a session.
        $sql = "SELECT * FROM sessions WHERE id = ? AND series = ? AND token = ?;";
        $sessions = $this->dbhr->preQuery($sql, [
            $id,
            $series,
            $token
        ]);

        #error_log("SELECT * FROM sessions WHERE id = $id AND series = $series AND token = '$token';");

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($sessions as $session) {
            # Remove it and create a new one, which will be returned in a cookie.
            # This means that our cookies are one-use only.
            #error_log("Found it, create new");
            $userid = $session['userid'];
            $this->create($userid);
            $_SESSION['id'] = $userid;
            $_SESSION['logged_in'] = TRUE;
            return($userid);
        }

        # We failed to verify.  If the ID and series are present, then it is likely to be
        # a theft.  Delete any existing sessions.  If they aren't, then the delete won't do anything.
        $this->destroy($id);

        return(NULL);
    }

    public function destroy($userid) {
        #error_log(var_export($this->dbhr, true));
        session_reopen();

        if ($userid) {
            $sql = "DELETE FROM sessions WHERE userid = ?;";
            $this->dbhm->preExec($sql, [
                $userid
            ]);
        }

        $_SESSION['id'] = NULL;
        $_SESSION['logged_in'] = FALSE;
    }
}

function session_reopen() {
    # Generally we close the session for write access in whoAmI().  This allows us to get
    # write access back.
    ini_set('session.use_only_cookies', false);
    ini_set('session.use_cookies', false);
    ini_set('session.cache_limiter', null);
    @session_start(); //Reopen the (previously closed) session for writing.
}