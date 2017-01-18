<?php
function stories() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $id = presdef('id', $_REQUEST, NULL);
    $s = new Story($dbhr, $dbhm, $id);
    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [ 'ret' => 3, 'status' => 'Invalid id' ];
            if ($id) {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                # Can see our own, or all if we have permissions.
                if ($s->getPrivate('userid') == $myid || $me->isAdminOrSupport()) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'story' => $s->getPublic()
                    ];
                }
            }

            break;
        }

        case 'PUT':
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            if ($me) {
                $id = $s->create($me->getId(), presdef('headline', $_REQUEST, NULL), presdef('story', $_REQUEST, NULL));
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
            }
            break;

        case 'PATCH': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            if ($s->getPrivate('userid') == $myid || $me->isAdminOrSupport()) {
                $s->setAttributes($_REQUEST);
                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }
            break;
        }

        case 'DELETE': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            if ($s->getPrivate('userid') == $myid || $me->isAdminOrSupport()) {
                $s->delete();

                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }
        }
    }

    return($ret);
}
