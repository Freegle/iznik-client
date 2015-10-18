<?php
function message() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $id = intval(presdef('id', $_REQUEST, NULL));
    $reason = presdef('reason', $_REQUEST, NULL);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $action = presdef('action', $_REQUEST, NULL);
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
        case 'DELETE': {
            $m = NULL;
            $m = new Message($dbhr, $dbhm, $id);

            if (!$m->getID()) {
                $ret = ['ret' => 3, 'status' => 'Message does not exist'];
                $m = NULL;
            } else {
                switch ($collection) {
                    case Collection::APPROVED:
                        break;
                    case Collection::PENDING:
                        if (!$me) {
                            $ret = ['ret' => 1, 'status' => 'Not logged in'];
                            $m = NULL;
                        } else {
                            if (!$me->isModOrOwner($m->getGroups()[0])) {
                                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                                $m = NULL;
                            }
                        }
                        break;
                    case Collection::SPAM:
                        if (!$me) {
                            $ret = ['ret' => 1, 'status' => 'Not logged in'];
                            $m = NULL;
                        } else {
                            if (!$me->isModOrOwner($m->getGroups()[0])) {
                                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                                $m = NULL;
                            }
                        }
                        break;
                }
            }

            if ($m) {
                if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'message' => $m->getPublic()
                    ];
                } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                    if (!$me->isModOrOwner($m->getGroups()[0])) {
                        $ret = ['ret' => 2, 'status' => 'Permission denied'];
                    } else {
                        $m->delete($reason);
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];
                    }
                }
            }
        }
        break;

        case 'POST': {
            $m = new Message($dbhr, $dbhm, $id);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($m && $me && $me->isModOrOwner($groupid)) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];

                if ($action == 'Delete') {
                    if ($m->isPending($groupid)) {
                        # We have to reject on Yahoo, but without a reply.
                        $m->reject($groupid, NULL, NULL);
                    } else {
                        $m->delete($reason);
                    }
                } else if ($action == 'Reject') {
                    if (!$m->isPending($groupid)) {
                        $ret = ['ret' => 3, 'status' => 'Message is not pending'];
                    } else {
                        $m->reject($groupid, $subject, $body);
                    }
                } else if ($action == 'Approve') {
                    if (!$m->isPending($groupid)) {
                        $ret = ['ret' => 3, 'status' => 'Message is not pending'];
                    } else {
                        $m->approve($groupid);
                    }
                } else if ($action == 'NotSpam') {
                    $r = new MailRouter($dbhr, $dbhm);
                    $r->route($m, TRUE);
                }
            }
        }
    }

    return($ret);
}
