<?php
function chatrooms() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : $me;

    $id = intval(presdef('id', $_REQUEST, NULL));
    $userid = intval(presdef('userid', $_REQUEST, NULL));
    $r = new ChatRoom($dbhr, $dbhm, $id);
    $chattypes = presdef('chattypes', $_REQUEST, [ ChatRoom::TYPE_USER2USER ]);
    $chattype = presdef('chattype', $_REQUEST, ChatRoom::TYPE_USER2USER);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));

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
                    
                    $rooms = $r->listForUser($myid, $chattypes);
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

        case 'PUT': {
            # Create a conversation.
            $ret = ['ret' => 1, 'status' => 'Not logged in'];

            if ($me) {
                $ret = ['ret' => 2, 'status' => 'Bad parameters'];

                error_log("Create $chattype");
                switch ($chattype) {
                    case ChatRoom::TYPE_USER2USER:
                        if ($userid) {
                            $id = $r->createConversation($myid, $userid);
                        }
                        break;
                    case ChatRoom::TYPE_USER2MOD:
                        $id = $r->createUser2Mod($myid, $groupid);
                        break;
                }

                $ret = ['ret' => 3, 'status' => 'Create failed'];
                if ($id) {
                    $ret = ['ret' => 0, 'status' => 'Success', 'id' => $id];
                }
            }
            break;
        }

        case 'POST': {
            # Update our presence and get the current roster.
            $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

            if ($me && $id) {
                $ret = ['ret' => 2, 'status' => "$id Not visible to you"];

                if ($r->canSee($me->getId())) {
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
    }

    return($ret);
}
