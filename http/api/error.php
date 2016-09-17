<?php
function error() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 0, 'status' => 'Success' ];

    switch ($_REQUEST['type']) {
        case 'PUT':
            $me = whoAmI($dbhr, $dbhm);
            $userid = $me ? $me->getId() : 'NULL';
            $type = $dbhm->quote(presdef('errortype', $_REQUEST, NULL));
            $text = $dbhm->quote(presdef('errortext', $_REQUEST, NULL));
            $sql = "INSERT INTO logs_errors (type, text, userid) VALUES ($type, $text, $userid);";
            $dbhm->background($sql);
            break;
    }

    return ($ret);
}