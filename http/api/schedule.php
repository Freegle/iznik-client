<?php
function schedule() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    $id = presdef('id', $_REQUEST, NULL);
    $s = new Schedule($dbhr, $dbhm, $id);
    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [ 'ret' => 3, 'status' => 'Invalid id' ];
            if ($id) {
                $ret = ['ret' => 2, 'status' => 'Permission denied'];

                $atts = $s->getPublic();

                if (in_array($myid, $atts['users'])) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'schedule' => $atts
                    ];
                }
            } else {
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'schedules' => $s->listForUser($myid)
                ];
            }

            break;
        }

        case 'POST':
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];
            if ($me) {
                $id = $s->create(presdef('schedule', $_REQUEST, NULL));
                $s->addUser($myid);
                $userid = intval(presdef('userid', $_REQUEST, NULL));
                $s->addUser($userid);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
            }
            break;

        case 'PATCH': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $atts = $s->getPublic();

            if (in_array($myid, $atts['users'])) {
                $s->setSchedule(presdef('schedule', $_REQUEST, NULL));
                $ret = [
                    'ret' => 0,
                    'status' => 'Success'
                ];
            }
            break;
        }
    }

    return($ret);
}
