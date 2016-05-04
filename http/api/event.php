<?php
function event() {
    global $dbhr, $dbhm;

    $events = array_key_exists('events', $_REQUEST) ? $_REQUEST['events'] : NULL;

    if ($events) {
        $p = new Events($dbhr, $dbhm);

        foreach ($events as $event) {
            $route = presdef('route', $event, NULL);
            $target = presdef('target', $event, NULL);
            $action = presdef('action', $event, NULL);
            $viewx = array_key_exists('viewx', $event) ? intval($event['viewx']) : NULL;
            $viewy = array_key_exists('viewy', $event) ? intval($event['viewy']) : NULL;
            $timestamp  = array_key_exists('timestamp', $event) ? intval($event['timestamp']) : NULL;
            $posx = array_key_exists('posX', $event) ? floatval($event['posX']) : NULL;
            $posy = array_key_exists('posY', $event) ? floatval($event['posY']) : NULL;
            $data = presdef('data', $event, NULL);

            error_log("Event route $route");
            if ($route !== NULL) {
                $p->record($route, $target, $action, $timestamp, $posx, $posy, $viewx, $viewy, $data);
            }
        }
    }

    $ret = array('ret' => 0, 'status' => 'Success');

    return ($ret);
}