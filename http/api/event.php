<?php
function event() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'POST':
            $events = array_key_exists('events', $_REQUEST) ? $_REQUEST['events'] : NULL;

            if ($events) {
                $p = new Events($dbhr, $dbhm);

                $me = whoAmI($dbhr, $dbhm);
                $myid = $me ? $me->getId() : NULL;
                $sessid = session_id();

                foreach ($events as $event) {
                    $route = presdef('route', $event, NULL);
                    $target = presdef('target', $event, NULL);
                    $action = presdef('event', $event, NULL);
                    $viewx = array_key_exists('viewx', $event) ? intval($event['viewx']) : NULL;
                    $viewy = array_key_exists('viewy', $event) ? intval($event['viewy']) : NULL;
                    $timestamp  = array_key_exists('timestamp', $event) ? intval($event['timestamp']) : NULL;
                    $posx = array_key_exists('posX', $event) ? floatval($event['posX']) : NULL;
                    $posy = array_key_exists('posY', $event) ? floatval($event['posY']) : NULL;
                    $data = presdef('data', $event, NULL);

                    if ($route !== NULL) {
                        $p->record($myid, $sessid, $route, $target, $action, $timestamp, $posx, $posy, $viewx, $viewy, $data);
                    }
                }

                $p->flush();
            }

            $ret = array('ret' => 0, 'status' => 'Success', 'nolog' => TRUE);
            break;
        case 'GET':
            $me = whoAmI($dbhr, $dbhm);
            $ret = array('ret' => 2, 'status' => 'Permission denied');

            if ($me && $me->isAdminOrSupport()) {
                $sessionid = presdef('sessionid', $_REQUEST, NULL);
                $p = new Events($dbhr, $dbhm);
                $events = $p->get($sessionid);
                $ret = !$events ? ['ret' => 1, 'status' => 'Session not found'] : ['ret' => 0, 'status' => 'Success', 'events' => $events];
            }

            break;
    }

    return ($ret);
}