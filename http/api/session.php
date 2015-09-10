<?php
function session() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        # Check if we're logged in
        if ($me) {
            $ret = array('ret' => 0, 'status' => 'Success', 'me' => $me->getPublic());
//            $ret['groups'] = $me->getGroups();
        } else {
            $ret = array('ret' => 1, 'status' => 'Not logged in');
        }
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            error_log("$email $password $possid");
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
    } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
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
        } catch (Exception $e) {}
    }

    error_log(var_export($ret, true));

    return($ret);
}
