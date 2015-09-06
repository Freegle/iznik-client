<?php

require_once("/etc/iznik.conf");
require_once(IZNIK_BASE . '/lib/openid.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/session/Session.php');

class Yahoo
{
    private $dbhr;
    private $dbhm;

    /** @var  $openid LightOpenID */
    private $openid;

    private static $instance;

    public static function getInstance($dbhr, $dbhm)
    {
        if (!isset(self::$instance)) {
            self::$instance = new Yahoo($dbhr, $dbhm);
        }
        return self::$instance;
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->openid = new LightOpenID;
        $this->openid->realm = "https://{$_SERVER['HTTP_HOST']}";

        return ($this);
    }
    
    /**
     * @param mixed $openid
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;
    }

    function login($returnto)
    {
        try
        {
            $loginurl = "https://{$_SERVER['HTTP_HOST']}/yahoologin?returnto=" . urlencode($returnto);
            $this->openid->returnUrl = $loginurl;

            if (($this->openid->validate()) &&
                ($this->openid->identity != 'https://open.login.yahooapis.com/openid20/user_profile/xrds'))
            {
                error_log("Validated Yahoo Login");
                $attrs = $this->openid->getAttributes();

                # The Yahoo ID is derived from the email; Yahoo always returns the Yahoo email even if a different
                # email is configured on the profile.  Way to go.
                $yahooid = $attrs['contact/email'];
                $p = strpos($yahooid, "@");
                $yahooid = substr($yahooid, 0, $p);

                # See if we know this user already.
                $users = $this->dbhr->preQuery(
                    "SELECT userid FROM users_logins WHERE type = 'Yahoo' AND uid = ?;",
                    [ $yahooid ]
                );

                $id = NULL;
                foreach ($users as $user) {
                    # We found them.
                    $id = $user['userid'];
                }

                error_log("Found them? $id");

                if (!$id) {
                    # We don't know them.  Create a user.
                    #
                    # There's a timing window here, where if we had two first-time logins for the same user,
                    # one would fail.  Bigger fish to fry.
                    $u = new User($this->dbhr, $this->dbhm);

                    # We don't have the firstname/lastname split, only a single name.  Way two go.
                    $id = $u->create(NULL, NULL, $attrs['name']);

                    if ($id) {
                        # Now Set up a login entry.
                        $rc = $this->dbhm->preExec(
                            "INSERT INTO users_logins (userid, type, uid) VALUES (?,'Yahoo',?);",
                            [
                                $id,
                                $yahooid
                            ]
                        );

                        $id = $rc ? $id : NULL;
                    }
                }

                if ($id) {
                    // We are logged in.
                    error_log("Logged in");
                    $s = new Session($this->dbhr, $this->dbhm);
                    $s->create($id);
                    return([ $s, [ 'ret' => 0, 'status' => 'Success']]);
                }
            } else if (!$this->openid->mode) {
                # We're not logged in.  Redirect to Yahoo to authorise.
                $this->openid->identity = 'https://me.yahoo.com';
                $this->openid->required = array('contact/email', 'namePerson', 'namePerson/first', 'namePerson/last');
                $this->openid->redirect_uri = $returnto;
                $url = $this->openid->authUrl() . "&key=Iznik";
                return [NULL, ['ret' => 1, 'redirect' => $url]];
            }
        }
        catch (Exception $e)
        {
            error_log("Yahoo Login exception " . $e->getMessage());
        }

        return ([NULL, [ 'ret' => 2, 'status' => 'Login failed']]);
    }
}