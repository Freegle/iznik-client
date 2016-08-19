<?php
function socialactions() {
    global $dbhr, $dbhm;

    $ctx = presdef('context', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET':
            $f = new GroupFacebook($dbhr, $dbhm);
            $actions = $f->listSocialActions($ctx);
            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'socialactions' => $actions,
                'context' => $ctx
            ];
           break;
    }

    return ($ret);
}