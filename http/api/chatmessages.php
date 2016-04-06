<?php
function chatmessages() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $roomid = intval(presdef('roomid', $_REQUEST, NULL));
    $message = presdef('message', $_REQUEST, NULL);

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
                }
            }
            break;
        }

        case 'POST': {
            $ret = ['ret' => 1, 'status' => 'Not logged in'];

            if ($me) {
                $ret = ['ret' => 2, 'status' => 'Not visible to you'];

                if ($message && $roomid && $r->canSee($me->getId())) {
                    $id = $m->create($roomid, $me->getId(), $message);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'id' => $id
                    ];
                }
            }

        }
    }

    return($ret);
}
