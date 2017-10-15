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

                # Create a message in a chat between the users to show that we have created this schedule.
                $r = new ChatRoom($dbhr, $dbhm);
                $rid = $r->createConversation($myid, $userid);
                $m = new ChatMessage($dbhr, $dbhm);
                $mid = $m->create($rid, $myid, NULL, ChatMessage::TYPE_SCHEDULE, NULL, TRUE, NULL, NULL, NULL, NULL, NULL, $id);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'id' => $id
                ];
            }
            break;

        case 'PATCH':
        case 'PUT': {
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $atts = $s->getPublic();

            if (in_array($myid, $atts['users'])) {
                $s->setSchedule(presdef('schedule', $_REQUEST, NULL));
                $agreed = presdef('agreed', $_REQUEST, NULL);

                foreach ($atts['users'] as $user) {
                    if ($user != $myid) {
                        # Create a message in a chat between the users to show that we have updated this schedule.
                        #
                        # Any agreed time is held in the message.
                        $r = new ChatRoom($dbhr, $dbhm);
                        $rid = $r->createConversation($myid, $user);
                        $m = new ChatMessage($dbhr, $dbhm);
                        $mid = $m->create($rid, $myid, $agreed, ChatMessage::TYPE_SCHEDULE_UPDATED, NULL, TRUE, NULL, NULL, NULL, NULL, NULL, $id);
                    }
                }

                if ($agreed) {
                    $s->setPrivate('agreed', $agreed);
                }

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
