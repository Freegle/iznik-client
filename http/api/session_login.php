<?php
function session_login() {
    global $dbhr, $dbhm;

    session_reopen();

    $fblogin = array_key_exists('fblogin', $_REQUEST) ? filter_var($_REQUEST['fblogin'], FILTER_VALIDATE_BOOLEAN) : false;
    $googlelogin = array_key_exists('googlelogin', $_REQUEST) ? filter_var($_REQUEST['googlelogin'], FILTER_VALIDATE_BOOLEAN) : false;
    $yahoologin = array_key_exists('yahoologin', $_REQUEST) ? filter_var($_REQUEST['yahoologin'], FILTER_VALIDATE_BOOLEAN) : false;
    $fbauthtoken = array_key_exists('fbauthtoken', $_REQUEST) ? $_REQUEST['fbauthtoken'] : NULL;
    $googleauthcode = array_key_exists('googleauthcode', $_REQUEST) ? $_REQUEST['googleauthcode'] : NULL;
    $mobile = array_key_exists('mobile', $_REQUEST) ? filter_var($_REQUEST['mobile'], FILTER_VALIDATE_BOOLEAN) : false;
    $email = array_key_exists('email', $_REQUEST) ? $_REQUEST['email'] : '';
    $password = array_key_exists('password', $_REQUEST) ? $_REQUEST['password'] : '';
    $key = array_key_exists('key', $_REQUEST) ? $_REQUEST['key'] : NULL;
    $returnto = array_key_exists('returnto', $_REQUEST) ? $_REQUEST['returnto'] : NULL;
    $rememberme = array_key_exists('rememberme', $_REQUEST) ? filter_var($_REQUEST['rememberme'], FILTER_VALIDATE_BOOLEAN) : false;

    $user = new User($dbhr, $dbhm);
    $f = NULL;
    $newuser = false;
    $ret = array('ret' => 1, 'status' => 'Invalid login details');

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

        $ret['session'] = session_id();
        $ret['user'] = $u->getPublic();
    }

    return ($ret);
}