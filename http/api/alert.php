<?php
function alert() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = presdef('id', $_REQUEST, NULL);
    $id = $id ? intval($id) : NULL;
    $a = new Alert($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            if ($id) {
                # We're not bothered about privacy of alerts - people may not be logged in when they see them.
                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'alert' => $a->getPublic()
                ];

                if ($me && $me->isAdminOrSupport()) {
                    $ret['alert']['stats'] = $a->getStats();
                }
            } else {
                # List all.
                $ret = ['ret' => 1, 'status' => 'Not logged in or can\'t do that'];

                if ($me && $me->isAdminOrSupport()) {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'alerts' => $a->getList()
                    ];
                }
            }
            break;
        }

        case 'POST': {
            $action = presdef('action', $_REQUEST, NULL);
            
            switch ($action) {
                case 'clicked': {
                    $trackid = intval(presdef('trackid', $_REQUEST, NULL));
                    $a->clicked($trackid);
                    break;
                }
            }

            $ret = ['ret' => 0, 'status' => 'Success'];
            break;
        }
        
        case 'PUT': {
            $ret = ['ret' => 1, 'status' => 'Not logged in'];

            if ($me && $me->isAdminOrSupport()) {
                $from = presdef('from', $_REQUEST, NULL);
                $to = presdef('to', $_REQUEST, 'Mods');
                $subject = presdef('subject', $_REQUEST, NULL);
                $text = presdef('text', $_REQUEST, NULL);
                $html = presdef('html', $_REQUEST, NULL);
                $groupid = presdef('groupid', $_REQUEST, NULL);
                $groupid = $groupid == 'AllFreegle' ? NULL : intval($groupid);
                
                $alertid = $a->create($groupid, $from, $to, $subject, $text, $html);

                $ret = $alertid ? ['ret' => 0, 'status' => 'Success', 'id' => $alertid] : ['ret' => 2, 'status' => 'Create failed'];
            }
        }
    }

    return($ret);
}
