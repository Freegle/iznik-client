<?php
define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/misc/Shortlink.php');

$name = presdef('name', $_REQUEST, NULL);
$url = "https://" . USER_SITE;

if ($name) {
    $s = new Shortlink($dbhr, $dbhm);
    list ($id, $redirect) = $s->resolve($name);

    if ($id) {
        $url = $redirect;
    }
}

header("Location: $url");
