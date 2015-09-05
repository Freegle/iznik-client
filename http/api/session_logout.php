<?php
function session_logout() {
    global $dbhr, $dbhm;

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
    session_destroy();
    session_unset();
    session_start();
    session_regenerate_id(true);

    return($ret);
}
