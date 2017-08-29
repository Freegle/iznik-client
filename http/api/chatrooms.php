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
                $ret['chatroom'] = NULL;

                if ($r->canSee($myid)) {
                    $ret['chatroom'] = $r->getPublic();
                    $ret['chatroom']['unseen'] = $r->unseenCountForUser($myid);
                    $ret['chatroom']['lastmsgseen'] = $r->lastSeenForUser($myid);
                }
            } else {
                $ctx = NULL;
                $mepub = $me ? $me->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE, FALSE) : NULL;
                $ret = [ 'ret' => 1, 'status' => 'Not logged in' ];

                if ($me) {
                    $ret = [ 'ret' => 0, 'status' => 'Success' ];
                    
                    $rooms = $r->listForUser($myid, $chattypes, $search);
                    $ret['chatrooms'] = [];

                    if ($rooms) {
                        foreach ($rooms as $room) {
                            $r = new ChatRoom($dbhr, $dbhm, $room);
                            $atts = $r->getPublic($me, $mepub);
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
            $action = presdef('action', $_REQUEST, NULL);

            if ($me) {
                if ($action == 'AllSeen') {
                    $chatids = $r->listForUser($myid);

                    foreach ($chatids as $chatid) {
                        $r = new ChatRoom($dbhr, $dbhm, $chatid);

                        if ($r->unseenCountForUser($myid) > 0) {
                            $r->upToDate($myid);
                        }
                    }
                } else if ($action == 'Nudge') {
                    $id = $r->nudge();
                    $ret = ['ret' => 0, 'status' => 'Success', 'id' => $id];
                } else if ($id) {
                    # Single roster update.
                    $ret = ['ret' => 2, 'status' => "$id Not visible to you"];

                    if ($r->canSee($myid)) {
                        $ret = ['ret' => 0, 'status' => 'Success'];
                        $lastmsgseen = presdef('lastmsgseen', $_REQUEST, NULL);
                        $status = presdef('status', $_REQUEST, ChatRoom::STATUS_ONLINE);
                        $r->updateRoster($myid, $lastmsgseen, $status);

                        $ret['roster'] = $r->getRoster();
                        $ret['unseen'] = $r->unseenCountForUser($myid);
                        $ret['nolog'] = TRUE;
                    }
                }
            }
        }
    }

    return($ret);
}
