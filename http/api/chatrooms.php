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
    $search = presdef('search', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            if ($id) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];
                $ret['chatroom'] = $r->canSee($myid) ? $r->getPublic() : NULL;
            } else {
                $me = whoAmI($dbhr, $dbhm);
                $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

                if ($me) {
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];
                    
                    $rooms = $r->listForUser($myid, $chattypes, $search);
                    $ret['chatrooms'] = [];

                    if ($rooms) {
                        foreach ($rooms as $room) {
                            $r = new ChatRoom($dbhr, $dbhm, $room);
                            $atts = $r->getPublic();
                            $atts['unseen'] = $r->unseenCountForUser($myid);
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

            if ($me) {
                if ($id) {
                    # Single roster update.
                    $ret = ['ret' => 2, 'status' => "$id Not visible to you"];

                    if ($r->canSee($myid)) {
                        $ret = ['ret' => 0, 'status' => 'Success'];
                        $lastmsgseen = presdef('lastmsgseen', $_REQUEST, NULL);
                        $status = presdef('status', $_REQUEST, 'Online');
                        $r->updateRoster($myid, $lastmsgseen, $status);

                        $ret['roster'] = $r->getRoster();
                        $ret['unseen'] = $r->unseenCountForUser($myid);
                        $ret['nolog'] = TRUE;
                    }
                } else {
                    # Bulk roster update
                    $ret = ['ret' => 0, 'status' => 'Success', 'rosters' => [], 'nolog' => TRUE];
                    $rosters = presdef('rosters', $_REQUEST, []);
                    foreach ($rosters as $roster) {
                        $r = new ChatRoom($dbhr, $dbhm, $roster['id']);
                        if ($r->canSee($myid)) {
                            $r->updateRoster($myid, presdef('lastmsgseen', $roster, NULL), presdef('status', $roster, ChatRoom::STATUS_ONLINE));
                            $ret['rosters'][$roster['id']] = $r->getRoster();
                            $ret['unseen'][$roster['id']] = $r->unseenCountForUser($myid);
                        }
                    }
                }
            }
        }
    }

    return($ret);
}
