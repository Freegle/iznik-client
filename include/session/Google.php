<?php

require_once("/etc/iznik.conf");
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

class Google
{
    /** @var LoggedPDO $dbhr */
    /** @var LoggedPDO $dbhm */
    private $dbhr;
    private $dbhm;
    private $access_token;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $mobile)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->client = new Google_Client();
        $this->client->setApplicationName(GOOGLE_APP_NAME);

        $this->client->setClientId(GOOGLE_CLIENT_ID);
        $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);

        # This is required for the mobile app to work.  For some reason.
        if ($mobile) {
            $this->client->setRedirectUri('http://localhost');
        } else {
            $this->client->setRedirectUri('postmessage');
        }

        $this->client->setAccessType('offline');
        $this->client->setScopes(array(
            'https://www.googleapis.com/auth/email',
            'https://www.googleapis.com/auth/profile'));

        $this->plus = new Google_Service_Plus($this->client);

        return ($this);
    }

    public function getClient() {
        return($this->client);
    }

    public function getPlus() {
        return($this->plus);
    }

    function login($code)
    {
        $uid = NULL;
        $ret = 2;
        $status = 'Login failed';
        $s = NULL;

        try {
            $client = $this->getClient();
            $plus = $this->getPlus();
            $client->authenticate($code);
            $this->access_token = $client->getAccessToken();
            $this->tokens_decoded = json_decode($this->access_token);
            $me = $plus->people->get("me");

            /** @var Google_Service_Plus_Person $emails */
            $emails = $me->getEmails();
            $googlemail = NULL;

            foreach ($emails as $anemail) {
                if ($anemail->getType() == 'account') {
                    $googlemail = $anemail->getValue();
                }
            }

            $googleuid = presdef('id', $me, NULL);
            #error_log("Google id " . var_export($googleuid, TRUE));
            $firstname = NULL;
            $lastname = NULL;
            $fullname = $me['displayName'];

            # See if we know this user already.  We might have an entry for them by email, or by Facebook ID.
            $u = new User($this->dbhr, $this->dbhm);
            $eid = $googlemail ? $u->findByEmail($googlemail) : NULL;
            $gid = $googleuid ? $u->findByLogin('Google', $googleuid) : NULL;
            #error_log("Email $eid  from $googlemail Google $gid, f $firstname, l $lastname, full $fullname");

            if ($eid && $gid && $eid != $gid) {
                # This is a duplicate user.  Merge them.
                $u = new User($this->dbhr, $this->dbhm);
                $u->merge($eid, $gid, "Google Login - GoogleID $gid, Email $googlemail = $eid");
            }

            $id = $eid ? $eid : $gid;
            #error_log("Login id $id from $eid and $gid");

            if (!$id) {
                # We don't know them.  Create a user.
                #
                # There's a timing window here, where if we had two first-time logins for the same user,
                # one would fail.  Bigger fish to fry.
                #
                # We don't have the firstname/lastname split, only a single name.  Way two go.
                $id = $u->create($firstname, $lastname, $fullname, "Google login from $gid");

                if ($id) {
                    # Make sure that we have the Yahoo email recorded as one of the emails for this user.
                    $u = new User($this->dbhr, $this->dbhm, $id);

                    if ($googlemail) {
                        $u->addEmail($googlemail, 0, FALSE);
                    }

                    # Now Set up a login entry.  Use IGNORE as there is a timing window here.
                    $rc = $this->dbhm->preExec(
                        "INSERT IGNORE INTO users_logins (userid, type, uid) VALUES (?,'Google',?);",
                        [
                            $id,
                            $googleuid
                        ]
                    );

                    $id = $rc ? $id : NULL;
                }
            } else {
                # We know them - but we might not have all the details.
                $u = new User($this->dbhr, $this->dbhm, $id);

                if (!$eid) {
                    $u->addEmail($googlemail, 0, FALSE);
                }

                if (!$gid) {
                    $this->dbhm->preExec(
                        "INSERT IGNORE INTO users_logins (userid, type, uid) VALUES (?,'Google',?);",
                        [
                            $id,
                            $googleuid
                        ]
                    );
                }
            }

            # Save off the access token, which we might need, and update the access time.
            $this->dbhm->preExec("UPDATE users_logins SET lastaccess = NOW(), credentials = ? WHERE userid = ? AND type = 'Google';",
                [
                    (string)$this->access_token,
                    $id
                ]);

            # We have publish permissions for users who login via our platform.
            $u->setPrivate('publishconsent', 1);

            # We might have syncd the membership without a good name.
            $u->setPrivate('firstname', $firstname);
            $u->setPrivate('lastname', $lastname);
            $u->setPrivate('fullname', $fullname);

            if ($id) {
                # We are logged in.
                $s = new Session($this->dbhr, $this->dbhm);
                $s->create($id);

                # Anyone who has logged in to our site has given RIPA consent.
                $this->dbhm->preExec("UPDATE users SET ripaconsent = 1 WHERE id = ?;",
                    [
                        $id
                    ]);

                $l = new Log($this->dbhr, $this->dbhm);
                $l->log([
                    'type' => Log::TYPE_USER,
                    'subtype' => Log::SUBTYPE_LOGIN,
                    'byuser' => $id,
                    'text' => "Using Google $googleuid"
                ]);

                $ret = 0;
                $status = 'Success';
            }
        } catch (Exception $e) {
            $ret = 2;
            $status = "Didn't manage to get a Google session: " . $e->getMessage();
        }

        return ([$s, [ 'ret' => $ret, 'status' => $status]]);
    }
}