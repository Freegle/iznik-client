<?php
function profile() {
    global $dbhr, $dbhm;

    $id = intval(presdef('id', $_REQUEST, 0));
    $hash = presdef('hash', $_REQUEST, NULL);
    $def = presdef('d', $_REQUEST, 'https://' . USER_SITE . '/images/defaultprofile.png');

    $u = new User($dbhr, $dbhm);

    $id = $id ? $id : $u->findByEmailHash($hash);

    $ret = [ 'ret' => 1, 'status' => 'Unknown hash' ];
    $url = $def;

    if ($id) {
        $u = new User($dbhr, $dbhm, $id);
        $ctx = NULL;
        $atts = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE, FALSE);

        $url = $atts['profile']['default'] ? $def : $atts['profile']['url'];
    }

    header('Location: ' . $url);
    exit(0);
}
