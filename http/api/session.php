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

                $ret['configs'] = $me->getConfigs();
                $ret['emails'] = $me->getEmails();
                $ret['groups'] = $me->getMemberships();
                $ret['work'] = [];

                foreach ($ret['groups'] as $group) {
                    if (pres('work', $group)) {
                        foreach ($group['work'] as $key => $work) {
                            if (pres($key, $ret['work'])) {
                                $ret['work'][$key] += $work;
                            } else {
                                $ret['work'][$key] = $work;
                            }
                        }
                    }
                }

                $s = new Spam($dbhr, $dbhm);
                $ret['work']['spammerpendingadd'] = $s->collectionCount(Spam::TYPE_PENDING_ADD);
                $ret['work']['spammerpendingremove'] = $s->collectionCount(Spam::TYPE_PENDING_REMOVE);
            } else {
                $ret = array('ret' => 1, 'status' => 'Not logged in');
            }

            break;
        }

        case 'POST': {
            # Login
            session_reopen();

            $fblogin = array_key_exists('fblogin', $_REQUEST) ? filter_var($_REQUEST['fblogin'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $googlelogin = array_key_exists('googlelogin', $_REQUEST) ? filter_var($_REQUEST['googlelogin'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $yahoologin = array_key_exists('yahoologin', $_REQUEST) ? filter_var($_REQUEST['yahoologin'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $googleauthcode = array_key_exists('googleauthcode', $_REQUEST) ? $_REQUEST['googleauthcode'] : NULL;
            $mobile = array_key_exists('mobile', $_REQUEST) ? filter_var($_REQUEST['mobile'], FILTER_VALIDATE_BOOLEAN) : FALSE;
            $email = array_key_exists('email', $_REQUEST) ? $_REQUEST['email'] : NULL;
            $password = array_key_exists('password', $_REQUEST) ? $_REQUEST['password'] : NULL;
            $key = array_key_exists('key', $_REQUEST) ? $_REQUEST['key'] : NULL;
            $returnto = array_key_exists('returnto', $_REQUEST) ? $_REQUEST['returnto'] : NULL;
            $rememberme = array_key_exists('rememberme', $_REQUEST) ? filter_var($_REQUEST['rememberme'], FILTER_VALIDATE_BOOLEAN) : FALSE;

            $id = NULL;
            $user = new User($dbhr, $dbhm);
            $f = NULL;
            $ret = array('ret' => 1, 'status' => 'Invalid login details');

            if ($password && $email) {
                # Native login via username and password
                $ret = array('ret' => 2, 'status' => "We don't know that email address.  If you're new, please Sign Up.");
                $possid = $user->findByEmail($email);
                if ($possid) {
                    $ret = array('ret' => 3, 'status' => "The password is wrong.  Maybe you've forgotten it?");
                    $u = new User($dbhr, $dbhm, $possid);
                    if ($u->login($password)) {
                        $ret = array('ret' => 0, 'status' => 'Success');
                        $id = $possid;
                    }
                }
            } else if ($fblogin) {
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
            }

            if ($id) {
                # Return some more useful info.
                $u = new User($dbhr, $dbhm, $id);
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
            # Logout
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
