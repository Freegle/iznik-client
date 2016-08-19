<?php
function socialactions() {
    global $dbhr, $dbhm;

    $ctx = presdef('context', $_REQUEST, NULL);
    $id = presdef('id', $_REQUEST, NULL);
    $id = $id ? intval($id) : $id;
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));

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

        case 'POST':
            $f = new GroupFacebook($dbhr, $dbhm, $groupid);
            $f->performSocialAction($id);
            $ret = [
                'ret' => 0,
                'status' => 'Success'
            ];
            break;
    }

    return ($ret);
}