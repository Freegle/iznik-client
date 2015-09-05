<?php

require_once(IZNIK_BASE . '/include/utils.php');

# You'll notice some complex stuff in here to do with sessions.  By default in PHP you use session_start, but this is
# quite expensive, so we only want to call it from within whoAmI() when we know we need it.  Again by default
# once you've called it, the session is then blocked, which is bad for simultaneous AJAX calls, so we unlock write access
# unless told to keep it.  We also have a call to reclaim write access to the session for some cases.

if (!isset($_SESSION)) {
    session_start();
}

function prepareSession($dbhr, $dbhm) {
    if ((!array_key_exists('id', $_SESSION)) &&
        (array_key_exists(COOKIE_NAME, $_COOKIE))) {
        # Check our cookie to see if it's a valid session
        $cookie = json_decode($_COOKIE[COOKIE_NAME], true);

        if ((array_key_exists('id', $cookie)) &&
            (array_key_exists('series', $cookie)) &&
            (array_key_exists('token', $cookie))) {
            $s = new Session($dbhr, $dbhm);

            if ($s->verify($cookie['id'], $cookie['series'], $cookie['token'])) {
                # It's valid.  Log us in
                $_SESSION['logged_in']  = TRUE;
                $_SESSION['id'] = $cookie['id'];
            }
        }
    } else {
        #error_log("No cookie or logged in");
    }
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
        $sql = "SELECT * FROM users WHERE id = $id;";
        $users = $dbhr->query($sql);
        foreach ($users as $user) {
            $ret = $user;
        }
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

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    public function create($id) {
        # Destroy any existing sessions.
        $this->destroy($id);
        $this->id = $id;

        # Generate a new series and token.
        $series = devurandom_rand();
        $token  = devurandom_rand();
        $thash  = sha1($token);

        $sql = "INSERT INTO sessions (`id`, `series`, `token`) VALUES (?,?,?);";
        $this->dbhm->preExec($sql, [
            $id,
            $series,
            $thash
        ]);

        $_SESSION['id'] = $id;

        return (array(
            'id' => $id,
            'series' => $series,
            'token' => $token
        ));
    }

    public function verify($id, $series, $token) {
        # Look for a session.
        $thash  = sha1($token);
        $sql = "SELECT * FROM sessions WHERE id = ? AND series = ? AND token = ?;";
        $sessions = $this->dbhr->preQuery($sql, [
            $id,
            $series,
            $thash
        ]);

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($sessions as $session) {
            # Remove it and create a new one, which will be returned in a cookie.
            # This means that our cookies are one-use only.
            return($this->create($id));
        }

        # We failed to verify.  If the ID and series are present, then it is likely to be
        # a theft.  Delete any existing sessions.  If they aren't, then the delete won't do anything.
        $this->destroy($id);

        return(NULL);
    }

    public function destroy($id) {
        #error_log(var_export($this->dbhr, true));
        if ($id) {
            $sql = "DELETE FROM sessions WHERE id = ?;";
            $this->dbhm->preExec($sql, [
                $id
            ]);
        }
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