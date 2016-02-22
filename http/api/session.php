<?php
function session() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            # Check if we're logged in
            if ($me && $me->getId()) {
                $ret = array('ret' => 0, 'status' => 'Success', 'me' => $me->getPublic());

                # Don't need to return this, and it might be large.
                $ret['me']['messagehistory'] = NULL;

                $n = new Notifications($dbhr, $dbhm);
                $ret['me']['notifications'] = [
                    'push' => $n->get($ret['me']['id'])
                ];

                $ret['groups'] = $me->getMemberships();
                $ret['configs'] = $me->getConfigs();
                $ret['emails'] = $me->getEmails();
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
                $ret['work']['spammembers'] = $s->workCount();
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
            $fbauthtoken = array_key_exists('fbauthtoken', $_REQUEST) ? $_REQUEST['fbauthtoken'] : NULL;
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
                $possid = $user->findByEmail($email);
                if ($possid) {
                    $u = new User($dbhr, $dbhm, $possid);
                    if ($u->login($password)) {
                        $ret = array('ret' => 0, 'status' => 'Success');
                        $id = $possid;
                    }
                }
            }

            if ($fblogin) {
                # We've been asked to log in via Facebook.
                //        $f = new Facebook($dbhr, $dbhm);
                //        list ($id, $ret, $success, $newuser) = $f->login($fbauthtoken);
            } else if ($yahoologin) {
                # Yahoo.
                $y = Yahoo::getInstance($dbhr, $dbhm);
                list ($session, $ret) = $y->login($returnto);
                /** @var Session $session */
                $id = $session ? $session->getId() : NULL;
            }
            //    else if ($googlelogin) {
            # Google
            //        $g = new Google($dbhr, $dbhm, $mobile);
            //        list ($id, $ret, $success, $newuser) = $g->login($googleauthcode);
            //    } else {
            //        list ($id, $success) = $user->login_check($email, $password, $key, $rememberme);
            //        $_SESSION['sesstype'] = 'Freegle';
            //    }

            if ($id) {
                # Return some more useful info.
                $u = new User($dbhr, $dbhm, $id);
                $ret['user'] = $u->getPublic();
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

                if ($fullname) {
                    $me->setPrivate('fullname', $fullname);
                }
                if ($firstname) {
                    $me->setPrivate('firstname', $firstname);
                }
                if ($lastname) {
                    $me->setPrivate('lastname', $lastname);
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

                $email = presdef('email', $_REQUEST, NULL);

                $ret = ['ret' => 0, 'status' => 'Success'];

                if ($email) {
                    $rc = $me->addEmail($email, 1);
                    if (!$rc) {
                        $ret = ['ret' => 3, 'status' => 'Email already in use by another user'];
                    }
                }
            }
            break;
        }

        case 'DELETE': {
            # Logout
            $id = pres('id', $_SESSION);
            if ($id) {
                $s = new Session($dbhr, $dbhm);
                $s->destroy($id);
            }

            $ret = array('ret' => 0, 'status' => 'Success');

            # Try to remove any persistent session cookie, though it would not be valid
            # even if presented.
            @setcookie(COOKIE_NAME, '', time() - 3600);

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
