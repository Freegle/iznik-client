<?php

require_once("/etc/iznik.conf");
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

use Facebook\FacebookSession;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\FacebookCanvasLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

class Facebook
{
    /** @var LoggedPDO $dbhr */
    /** @var LoggedPDO $dbhm */
    private $dbhr;
    private $dbhm;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        return ($this);
    }

    public function getFB() {
        $fb = new Facebook\Facebook([
            'app_id' => FBAPP_ID,
            'app_secret' => FBAPP_SECRET
        ]);

        return($fb);
    }

    function login()
    {
        $uid = NULL;
        $ret = 2;
        $status = 'Login failed';
        $s = NULL;

        $fb = $this->getFB();

        $helper = $fb->getJavaScriptHelper();

        try {
            $accessToken = $helper->getAccessToken();

            if ($accessToken) {
                // The OAuth 2.0 client handler helps us manage access tokens
                $oAuth2Client = $fb->getOAuth2Client();

                // Get the access token metadata from /debug_token
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);
                #error_log("Token metadata " . var_export($tokenMetadata, TRUE));

                // Validation (these will throw FacebookSDKException's when they fail)
                $tokenMetadata->validateAppId(FBAPP_ID);
                $tokenMetadata->validateExpiration();

                if (!$accessToken->isLongLived()) {
                    // Exchanges a short-lived access token for a long-lived one
                    try {
                        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                    } catch (Facebook\Exceptions\FacebookSDKException $e) {
                        # No need to fail the login = proceed with our short one.
                        error_log("Error getting long-lived access token: " . $e->getMessage());
                    }

                    #error_log("Got long lived access token " . var_export($accessToken->getValue(), TRUE));
                }

                if ($accessToken) {
                    try {
                        # We think we have a session.  See if we can get our data.
                        #
                        # Note that we may not get an email, and nowadays the id we are given is a per-app id not
                        # something that can be used to identify the user.
                        $response = $fb->get('/me?fields=id,name,first_name,last_name,email', $accessToken);
                        $fbme = $response->getGraphUser()->asArray();

                        $fbemail = presdef('email', $fbme, NULL);
                        $fbuid = presdef('id', $fbme, NULL);
                        $firstname = presdef('first_name', $fbme, NULL);
                        $lastname = presdef('last_name', $fbme, NULL);
                        $fullname = presdef('name', $fbme, NULL);

                        # See if we know this user already.  We might have an entry for them by email, or by Facebook ID.
                        $u = new User($this->dbhr, $this->dbhm);
                        $eid = $fbemail ? $u->findByEmail($fbemail) : NULL;
                        $fid = $fbuid ? $u->findByLogin('Facebook', $fbuid) : NULL;
                        #error_log("Email $eid  from $fbemail Facebook $fid, f $firstname, l $lastname, full $fullname");

                        if ($eid && $fid && $eid != $fid) {
                            # This is a duplicate user.  Merge them.
                            $u = new User($this->dbhr, $this->dbhm);
                            $u->merge($eid, $fid, "Facebook Login - FacebookID $fid, Email $fbemail = $eid");
                        }

                        $id = $eid ? $eid : $fid;
                        #error_log("Login id $id from $eid and $fid");

                        if (!$id) {
                            # We don't know them.  Create a user.
                            #
                            # There's a timing window here, where if we had two first-time logins for the same user,
                            # one would fail.  Bigger fish to fry.
                            #
                            # We don't have the firstname/lastname split, only a single name.  Way two go.
                            $id = $u->create($firstname, $lastname, $fullname, "Facebook login from $fid");

                            if ($id) {
                                # Make sure that we have the Yahoo email recorded as one of the emails for this user.
                                $u = new User($this->dbhr, $this->dbhm, $id);

                                if ($fbemail) {
                                    $u->addEmail($fbemail, 0, FALSE);
                                }

                                # Now Set up a login entry.  Use IGNORE as there is a timing window here.
                                $rc = $this->dbhm->preExec(
                                    "INSERT IGNORE INTO users_logins (userid, type, uid) VALUES (?,'Facebook',?);",
                                    [
                                        $id,
                                        $fbuid
                                    ]
                                );

                                $id = $rc ? $id : NULL;
                            }
                        } else {
                            # We know them - but we might not have all the details.
                            $u = new User($this->dbhr, $this->dbhm, $id);
                            
                            if (!$eid) {
                                $u->addEmail($fbemail, 0, FALSE);
                            }

                            if (!$fid) {
                                $this->dbhm->preExec(
                                    "INSERT IGNORE INTO users_logins (userid, type, uid) VALUES (?,'Facebook',?);",
                                    [
                                        $id,
                                        $fbuid
                                    ]
                                );
                            }
                        }

                        # Save off the access token, which we might need, and update the access time.
                        $this->dbhm->preExec("UPDATE users_logins SET lastaccess = NOW(), credentials = ? WHERE userid = ? AND type = 'Facebook';",
                            [
                                (string)$accessToken,
                                $id
                            ]);

                        # We might have syncd the membership without a good name.
                        $u->setPrivate('firstname', $firstname);
                        $u->setPrivate('lastname', $lastname);
                        $u->setPrivate('fullname', $fullname);

                        if ($id) {
                            # We are logged in.
                            $s = new Session($this->dbhr, $this->dbhm);
                            $s->create($id);

                            $l = new Log($this->dbhr, $this->dbhm);
                            $l->log([
                                'type' => Log::TYPE_USER,
                                'subtype' => Log::SUBTYPE_LOGIN,
                                'byuser' => $id,
                                'text' => "Using Facebook $fid"
                            ]);

                            $ret = 0;
                            $status = 'Success';
                        }
                    } catch (Exception $e) {
                        $ret = 1;
                        $status = "Failed to get user details " . $e->getMessage();
                    }
                }
            }
       } catch (Exception $e) {
            $ret = 2;
            $status = "Didn't manage to get a Facebook session: " . $e->getMessage();
        }

        return ([$s, [ 'ret' => $ret, 'status' => $status]]);
    }
}