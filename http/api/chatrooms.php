<?php
function chatrooms() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : $me;

    $id = intval(presdef('id', $_REQUEST, NULL));
    $r = new ChatRoom($dbhr, $dbhm, $id);
    $modtools = array_key_exists('modtools', $_REQUEST) ? filter_var($_REQUEST['modtools'], FILTER_VALIDATE_BOOLEAN) : FALSE;

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
                    
                    $rooms = $r->listForUser($myid, $modtools);
                    $ret['chatrooms'] = [];

                    if ($rooms) {
                        foreach ($rooms as $room) {
                            $r = new ChatRoom($dbhr, $dbhm, $room);
                            $atts = $r->getPublic();
                            $atts['unseen'] = $r->unseenForUser($myid);
                            $atts['lastmsgseen'] = $r->lastSeenForUser($myid);
                            $ret['chatrooms'][] = $atts;
                        }
                    }
                }
            }
            break;
        }

        case 'POST': {
            # Update our presence and get the current roseter.
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

            if ($me && $id) {
                $ret = ['ret' => 0, 'status' => 'Success'];
                $lastmsgseen = presdef('lastmsgseen', $_REQUEST, NULL);
                $status = presdef('status', $_REQUEST, 'Online');
                $r->updateRoster($myid, $lastmsgseen, $status);

                $ret['roster'] = $r->getRoster();
                $ret['unseen'] = $r->unseenForUser($myid);
                $ret['nolog'] = TRUE;
            }
        }
    }

    return($ret);
}
