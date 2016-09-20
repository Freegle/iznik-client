<?php
require_once(IZNIK_BASE . '/mailtemplates/modtools/verifymail.php');

function session() {
    global $dbhr, $dbhm;

    # Don't want to use cached information when looking at our own session.
    $me = whoAmI($dbhm, $dbhm);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            # Check if we're logged in
            if ($me && $me->getId()) {
                $ret = array('ret' => 0, 'status' => 'Success', 'me' => $me->getPublic());

                $ret['persistent'] = presdef('persistent', $_SESSION, NULL);

                # Don't need to return this, and it might be large.
                $ret['me']['messagehistory'] = NULL;

                $n = new Notifications($dbhr, $dbhm);
                $ret['me']['notifications'] = [
                    'push' => $n->get($ret['me']['id'])
                ];

                if (MODTOOLS) {
                    # We cache the configs as they are expensive to get.
                    if (pres('configs', $_SESSION)) {
                        $ret['configs'] = $_SESSION['configs'];
                        $ret['configscached'] = TRUE;
                    } else {
                        $ret['configs'] = $me->getConfigs();
                        $_SESSION['configs'] = $ret['configs'];
                    }
                }

                $ret['emails'] = $me->getEmails();

                # Get groups including work when we're on ModTools; don't need that on the user site.
                $ret['groups'] = $me->getMemberships(FALSE, NULL, MODTOOLS);
                $ret['work'] = [];

                foreach ($ret['groups'] as &$group) {
                    if (pres('work', $group)) {
                        foreach ($group['work'] as $key => $work) {
                            if (pres($key, $ret['work'])) {
                                $ret['work'][$key] += $work;
                            } else {
                                $ret['work'][$key] = $work;
                            }
                        }
                    }

                    $ammod = $me->isModerator();

                    if (MODTOOLS && $ammod) {
                        # Return info on Twitter status.  This isn't secret info - we don't put anything confidential
                        # in here - but it's of no interest to members so there's no point delaying them by
                        # fetching it.
                        #
                        # Similar code in group.php.
                        $t = new Twitter($dbhr, $dbhm, $group['id']);
                        $atts = $t->getPublic();
                        unset($atts['token']);
                        unset($atts['secret']);
                        $atts['authdate'] = ISODate($atts['authdate']);
                        $group['twitter'] =  $atts;

                        # Ditto Facebook.
                        $f = new GroupFacebook($dbhr, $dbhm, $group['id']);
                        $atts = $f->getPublic();
                        unset($atts['token']);
                        $atts['authdate'] = ISODate($atts['authdate']);
                        $group['facebook'] =  $atts;
                    }
                }

                if (MODTOOLS) {
                    $s = new Spam($dbhr, $dbhm);
                    $ret['work']['spammerpendingadd'] = $s->collectionCount(Spam::TYPE_PENDING_ADD);
                    $ret['work']['spammerpendingremove'] = $s->collectionCount(Spam::TYPE_PENDING_REMOVE);

                    # Show social actions from last 4 days.
                    $ctx = NULL;
                    $starttime = date("Y-m-d H:i:s", strtotime("midnight 4 days ago"));
                    $f = new GroupFacebook($dbhr, $dbhm);
                    $ret['work']['socialactions'] = count($f->listSocialActions($ctx, $starttime));
                }

                $c = new ChatMessage($dbhr, $dbhm);
                $ret['work'] = array_merge($ret['work'], $c->getReviewCount($me));
            } else {
                $ret = array('ret' => 1, 'status' => 'Not logged in');
            }

            break;
        }

        case 'POST': {
            # Login
            $fblogin = array_key_exists('fblogin', $_REQUEST) ? filter_var($_REQUEST['fblogin'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $googlelogin = array_key_exists('googlelogin', $_REQUEST) ? filter_var($_REQUEST['googlelogin'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $yahoologin = array_key_exists('yahoologin', $_REQUEST) ? filter_var($_REQUEST['yahoologin'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $googleauthcode = array_key_exists('googleauthcode', $_REQUEST) ? $_REQUEST['googleauthcode'] : NULL;
            $mobile = array_key_exists('mobile', $_REQUEST) ? filter_var($_REQUEST['mobile'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $email = array_key_exists('email', $_REQUEST) ? $_REQUEST['email'] : NULL;
            $password = array_key_exists('password', $_REQUEST) ? $_REQUEST['password'] : NULL;
            $returnto = array_key_exists('returnto', $_REQUEST) ? $_REQUEST['returnto'] : NULL;
            $action = presdef('action', $_REQUEST, NULL);

            $id = NULL;
            $user = User::get($dbhr, $dbhm);
            $f = NULL;
            $ret = array('ret' => 1, 'status' => 'Invalid login details');

            if ($fblogin) {
                # We've been asked to log in via Facebook.
                $f = new Facebook($dbhr, $dbhm);
                list ($session, $ret) = $f->login();
                /** @var Session $session */
                $id = $session ? $session->getUserId() : NULL;
            } else if ($yahoologin) {
                # Yahoo.
                $y = Yahoo::getInstance($dbhr, $dbhm);
                list ($session, $ret) = $y->login($returnto);
                /** @var Session $session */
                $id = $session ? $session->getUserId() : NULL;
            } else if ($googlelogin) {
                # Google
                $g = new Google($dbhr, $dbhm, $mobile);
                list ($session, $ret) = $g->login($googleauthcode);
                /** @var Session $session */
                $id = $session ? $session->getUserId() : NULL;
            } else if ($action) {
                switch ($action) {
                    case 'LostPassword': {
                        $id = $user->findByEmail($email);
                        $ret = [ 'ret' => 2, "We don't know that email address" ];
                        
                        if ($id) {
                            $u = User::get($dbhr, $dbhm, $id);
                            $u->forgotPassword($email);
                            $ret = [ 'ret' => 0, 'status' => "Success" ];
                        }    
                        
                        break;
                    }
                }
            }
            else if ($password && $email) {
                # Native login via username and password
                $ret = array('ret' => 2, 'status' => "We don't know that email address.  If you're new, please Sign Up.");
                $possid = $user->findByEmail($email);
                if ($possid) {
                    $ret = array('ret' => 3, 'status' => "The password is wrong.  Maybe you've forgotten it?");
                    $u = User::get($dbhr, $dbhm, $possid);

                    # If we are currently logged in as an admin, then we can force a log in as anyone else.  This is
                    # very useful for debugging.
                    $force = $me && $me->isAdmin();

                    if ($u->login($password, $force)) {
                        $ret = array('ret' => 0, 'status' => 'Success');
                        $id = $possid;

                        # We have publish permissions for users who login via our platform.
                        $u->setPrivate('publishconsent', 1);
                    }
                }
            }

            if ($id) {
                # Return some more useful info.
                $u = User::get($dbhr, $dbhm, $id);
                $ret['user'] = $u->getPublic();
                $ret['persistent'] = presdef('persistent', $_SESSION, NULL);
            }

            break;
        }

        case 'PATCH': {
            if (!$me) {
                $ret = ['ret' => 1, 'status' => 'Not logged in'];
            } else {
                $fullname = presdef('displayname', $_REQUEST, NULL);
                $firstname = presdef('firstname', $_REQUEST, NULL);
                $lastname = presdef('lastname', $_REQUEST, NULL);
                $password = presdef('password', $_REQUEST, NULL);
                $key = presdef('key', $_REQUEST, NULL);

                if ($firstname) {
                    $me->setPrivate('firstname', $firstname);
                }
                if ($lastname) {
                    $me->setPrivate('lastname', $lastname);
                }
                if ($fullname) {
                    # Fullname is what we set from the client.  Zap the first/last names so that people who change
                    # their name for privacy reasons are respected.
                    $me->setPrivate('fullname', $fullname);
                    $me->setPrivate('firstname', NULL);
                    $me->setPrivate('lastname', NULL);
                }

                $settings = presdef('settings', $_REQUEST, NULL);
                if ($settings) {
                    $me->setPrivate('settings', json_encode($settings));
                }

                $notifs = presdef('notifications', $_REQUEST, NULL);
                if ($notifs) {
                    $n = new Notifications($dbhr, $dbhm);
                    $push = presdef('push', $notifs, NULL);
                    if ($push) {
                        switch ($push['type']) {
                            case Notifications::PUSH_GOOGLE:
                            case Notifications::PUSH_FIREFOX:
                                $n->add($me->getId(), $push['type'], $push['subscription']);
                                break;
                        }
                    }
                }

                $ret = ['ret' => 0, 'status' => 'Success'];

                $email = presdef('email', $_REQUEST, NULL);
                if ($email) {
                    if (!$me->verifyEmail($email)) {
                        $ret = ['ret' => 10, 'status' => "We've sent a verification mail; please check your mailbox." ];
                    }
                }

                if ($key) {
                    if (!$me->confirmEmail($key)) {
                        $ret = ['ret' => 11, 'status' => 'Confirmation failed'];
                    }
                }
                
                if ($password) {
                    $me->addLogin(User::LOGIN_NATIVE, $me->getId(), $password);
                }

                if (array_key_exists('onholidaytill', $_REQUEST)) {
                    $me->setPrivate('onholidaytill', $_REQUEST['onholidaytill']);
                }

                Session::clearSessionCache();
            }
            break;
        }

        case 'DELETE': {
            # Logout.  Kill all sessions for this user.
            $id = pres('id', $_SESSION);
            if ($id) {
                $s = new Session($dbhr, $dbhm);
                $s->destroy($id, NULL);
            }

            $ret = array('ret' => 0, 'status' => 'Success');

            # Destroy the PHP session
            try {
                session_destroy();
                session_unset();
                session_start();
                session_regenerate_id(true);
            } catch (Exception $e) {
            }

            break;
        }
    }

    return($ret);
}
