<?php
function chatmessages() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $roomid = intval(presdef('roomid', $_REQUEST, NULL));
    $message = presdef('message', $_REQUEST, NULL);
    $refmsgid = presdef('refmsgid', $_REQUEST, NULL);
    $refchatid = presdef('refchatid', $_REQUEST, NULL);
    $reportreason = presdef('reportreason', $_REQUEST, NULL);
    $ctx = presdef('context', $_REQUEST, NULL);

    $r = new ChatRoom($dbhr, $dbhm, $roomid);
    $id = intval(presdef('id', $_REQUEST, NULL));
    $m = new ChatMessage($dbhr, $dbhm, $id);
    $me = whoAmI($dbhr, $dbhm);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = ['ret' => 1, 'status' => 'Not logged in'];

            if ($me) {
                $ret = ['ret' => 2, 'status' => "$roomid Not visible to you"];
                if ($roomid && $r->canSee($me->getId())) {
                    if ($id) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'chatmessage' => $m->getPublic()
                        ];

                        # We don't want to show someone whether their messages are held for review.
                        unset($ret['chatmessage']['reviewrequired']);
                        unset($ret['chatmessage']['reviewedby']);
                        unset($ret['chatmessage']['reviewrejected']);

                        $u = new User($dbhr, $dbhm, $ret['chatmessage']['userid']);
                        $ret['chatmessage']['user'] = $u->getPublic(NULL, FALSE);
                    } else {
                        list($msgs, $users) = $r->getMessages();
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'chatmessages' => $msgs,
                            'chatusers' => $users
                        ];
                    }
                } else if ($me->isModerator()) {
                    # See if we have any messages for review.
                    $r = new ChatRoom($dbhr, $dbhm);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'chatmessages' => $r->getMessagesForReview($me, $ctx)
                    ];

                    $ret['context'] = $ctx;
                }
            }
            break;
        }

        case 'POST': {
            $ret = ['ret' => 1, 'status' => 'Not logged in'];

            if ($me) {
                $ret = ['ret' => 2, 'status' => 'Not visible to you'];
                $action = presdef('action', $_REQUEST, NULL);

                if ($action == ChatMessage::ACTION_APPROVE && $id) {
                    $m->approve($id);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($action == ChatMessage::ACTION_REJECT && $id) {
                    $m->reject($id);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];
                } else if ($message && $roomid && $r->canSee($me->getId())) {
                    if ($refmsgid) {
                        $type = ChatMessage::TYPE_INTERESTED;
                    } else if ($refchatid) {
                        $type = ChatMessage::TYPE_REPORTEDUSER;
                    } else {
                        $type = ChatMessage::TYPE_DEFAULT;
                    }

                    $id = $m->create($roomid,
                        $me->getId(),
                        $message,
                        $type,
                        $refmsgid,
                        TRUE,
                        NULL,
                        $reportreason,
                        $refchatid);
                    $ret = ['ret' => 3, 'status' => 'Message create failed'];

                    if ($id) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'id' => $id
                        ];
                    }
                }
            }
        }
        break;
    }

    return($ret);
}
