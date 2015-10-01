<?php
function message() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $collection = presdef('collection', $_REQUEST, Collection::APPROVED);
    $id = intval(presdef('id', $_REQUEST, NULL));
    $reason = presdef('reason', $_REQUEST, NULL);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $source = presdef('source', $_REQUEST, NULL);
    $from = presdef('from', $_REQUEST, NULL);
    $message = presdef('message', $_REQUEST, NULL);

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
        case 'DELETE': {
            $m = NULL;
            $m = new Message($dbhr, $dbhm, $id);
            error_log("Looking for message $id found " . $m->getID());

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

        case 'PUT': {
            # We are trying to sync a message.
            switch ($source) {
                case Message::YAHOO_PENDING:
                case Message::YAHOO_APPROVED:
                    break;
                default:
                    $source = NULL;
                    break;
            }

            $g = new Group($dbhr, $dbhm, $groupid);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];

            if ($g && $me && $me->isModOrOwner($groupid)) {
                $r = new MailRouter($dbhr, $dbhm);
                $r->received($source, $from, $g->getPrivate('nameshort') . '@yahoogroups.com', $message);
                $rc = $r->route();

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'routed' => $rc
                ];
            }
        }
    }

    return($ret);
}
