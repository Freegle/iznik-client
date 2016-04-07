<?php
function chatrooms() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : $me;

    $id = intval(presdef('id', $_REQUEST, NULL));
    $r = new ChatRoom($dbhr, $dbhm, $id);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            if ($id) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];
                $ret['chatroom'] = $r->getPublic();
            } else {
                $me = whoAmI($dbhr, $dbhm);
                $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

                if ($me) {
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];
                    
                    $rooms = $r->listForUser($myid);
                    $ret['chatrooms'] = [];

                    if ($rooms) {
                        foreach ($rooms as $room) {
                            $r = new ChatRoom($dbhr, $dbhm, $room);
                            $ret['chatrooms'][] = $r->getPublic();
                        }
                    }
                }
            }
            break;
        }

        case 'POST': {
            # Update our presence and get the current roseter.
            $me = whoAmI($dbhr, $dbhm);
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

            if ($me && $id) {
                $ret = ['ret' => 0, 'status' => 'Success'];
                $r->updateRoster($myid);
                $ret['roster'] = $r->getRoster();
            }
        }
    }

    return($ret);
}
