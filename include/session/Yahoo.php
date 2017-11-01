<?php

require_once("/etc/iznik.conf");
require_once(IZNIK_BASE . '/lib/openid.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

class Yahoo
{
    /** @var LoggedPDO $dbhr */
    /** @var LoggedPDO $dbhm */
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
        $this->openid->realm = getProtocol() . $_SERVER['HTTP_HOST'];

        return ($this);
    }
    
    /**
     * @param mixed $openid
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;
    }

    function login($returnto = '/')
    {
        try
        {
            $loginurl = getProtocol() . "{$_SERVER['HTTP_HOST']}/yahoologin?returnto=" . urlencode($returnto);
            $this->openid->returnUrl = $loginurl;

            if (($this->openid->validate()) &&
                ($this->openid->identity != 'https://open.login.yahooapis.com/openid20/user_profile/xrds'))
            {
                $attrs = $this->openid->getAttributes();

                # The Yahoo ID is derived from the email; Yahoo should always returns the Yahoo email even if a different
                # email is configured on the profile.  Way to go.
                #
                # But sometimes it doesn't return the email at all.  Way to go.  So in that case we use the namePerson
                # as though it was a Yahoo ID, since we have no other way to get it, and proceed without adding an
                # email.
                $yahooid = pres('contact/email', $attrs) ? $attrs['contact/email'] : $attrs['namePerson'];
                $p = strpos($yahooid, "@");
                $yahooid = $p != FALSE ? substr($yahooid, 0, $p) : $yahooid;

                # See if we know this user already.  We might have an entry for them by email, or by Yahoo ID.
                $u = User::get($this->dbhr, $this->dbhm);
                $eid = pres('contact/email', $attrs) ? $u->findByEmail($attrs['contact/email']) : NULL;
                $yid = $u->findByYahooId($yahooid);
                #error_log("Email $eid  from {$attrs['contact/email']} Yahoo $yid");

                if ($eid && $yid && $eid != $yid) {
                    # This is a duplicate user.  Merge them.
                    $u = User::get($this->dbhr, $this->dbhm);
                    $u->merge($eid, $yid, "Yahoo Login - YahooId $yahooid = $yid, Email {$attrs['contact/email']} = $eid");
                    #error_log("Yahoo login found duplicate user, merge $yid into $eid");
                }

                $id = $eid ? $eid : $yid;

                if (!$id) {
                    # We don't know them.  Create a user.
                    #
                    # There's a timing window here, where if we had two first-time logins for the same user,
                    # one would fail.  Bigger fish to fry.
                    #
                    # We don't have the firstname/lastname split, only a single name.  Way two go.
                    $id = $u->create(NULL, NULL, presdef('namePerson', $attrs, NULL), "Yahoo login from $yahooid");

                    if ($id) {
                        # Make sure that we have the Yahoo email recorded as one of the emails for this user.
                        $u = User::get($this->dbhr, $this->dbhm, $id);

                        if (pres('contact/email', $attrs)) {
                            $u->addEmail($attrs['contact/email'], 0, FALSE);
                        }

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

                $u = User::get($this->dbhr, $this->dbhm, $id);

                # We have publish permissions for users who login via our platform.
                $u->setPrivate('publishconsent', 1);

                # Make sure we record the most active yahooid for this user, rather than one we happened to pick
                # up on a group sync.
                $u->setPrivate('yahooid', $yahooid);

                $this->dbhm->preExec("UPDATE users_logins SET lastaccess = NOW() WHERE userid = ? AND type = 'Yahoo';",
                    [
                        $id
                    ]);

                if (!$u->getPrivate('fullname') && pres('namePerson', $attrs)) {
                    # We might have syncd the membership without a good name.
                    $u->setPrivate('fullname', $attrs['namePerson']);
                }

                if ($id) {
                    # We are logged in.
                    $s = new Session($this->dbhr, $this->dbhm);
                    $s->create($id);

                    # Anyone who has logged in to our site has given RIPA consent.
                    $this->dbhm->preExec("UPDATE users SET ripaconsent = 1 WHERE id = ?;",
                        [
                            $id
                        ]);
                    User::clearCache($id);

                    $l = new Log($this->dbhr, $this->dbhm);
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_LOGIN,
                        'byuser' => $id,
                        'text' => 'Using Yahoo ' . var_export($attrs, TRUE)
                    ]);

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