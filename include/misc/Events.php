<?php

use Pheanstalk\Pheanstalk;
require_once(IZNIK_BASE . '/include/utils.php');

class Events {
    private $dbhr;
    private $dbhm;

    private $queue = [];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
    }

    public function record($id, $sessid, $route, $target, $action, $timestamp, $posx, $posy, $viewx, $viewy, $data) {
        # TODO POST protection will stop blatant hacks but URLs with timestamps would get through.
        $timestamp = $timestamp ? ($timestamp * 0.001) : NULL;
        $hashvalue = $data ? md5($data) : NULL;
        $datasameas = NULL;

        if ($data) {
            # To save space in the table, we look for another record which has the same hash, and then data.  If we find
            # one, then can flag this entry as having the same data as that one.  This helps a lot, because we only have
            # a finite set of pages, and some of them are static, so our periodic DOM-f events would otherwise generate
            # a lot of data.
            $sql = "SELECT * FROM logs_events WHERE datahash = ? AND datasameas IS NULL LIMIT 10;";
            $start= microtime(TRUE);
            $logs = $this->dbhr->preQuery($sql, [ $hashvalue ]);
            foreach ($logs as $log) {
                $cmp = strcmp($log['data'], $data);
                if ($cmp == 0) {
                    $data = NULL;
                    $datasameas = $log['id'];
                    break;
                }
            }
        }

        $lastip = presdef('REMOTE_ADDR', $_SERVER, 'NULL');

        # Pass via beanstalk to the background job which will insert this efficiently into the DB.
        $this->queue[] = [
            'userid' => $id, 
            'sessionid' => $sessid,
            'clienttimestamp' => $timestamp,
            'route' => $route,
            'target' => $target,
            'event' => $action,
            'posx' => $posx,
            'posy' => $posy,
            'viewx' => $viewx,
            'viewy' => $viewy,
            'data' => $data,
            'datahash' => $hashvalue,
            'datasameas' => $datasameas,
            'ip' => $lastip
        ];
    }

    public function flush() {
        try {
            # If anything goes wrong, we're not that interested - we can lose events, and if we return errors the
            # client would retry.
            $id = $this->pheanstalk->put(json_encode([
                'type' => 'events',
                'queued' => time(),
                'ttr' => 300,
                'events' => $this->queue
            ]));

            $this->queue = [];
        } catch (Exception $e) {}
    }
    
    public function listSessions($userid = NULL) {
        $userq = $userid ? " WHERE userid = $userid " : '';
        $sql = "SELECT DISTINCT(sessionid) FROM logs_events $userq ORDER BY timestamp DESC LIMIT 100;";
        $sesslist = $this->dbhr->preQuery($sql);
        #error_log("Queryed, got " . count($sesslist));
        $ret = [];

        foreach ($sesslist as $asess) {
            $sessid = $asess['sessionid'];
            #error_log($sessid);
            $thisone = [
                'id' => $sessid
            ];

            $sql = "SELECT MAX(viewx) AS viewx, MAX(viewy) AS viewy, MAX(route) AS route, MAX(ip) AS ip, MAX(userid) AS userid, MIN(timestamp) AS start, MAX(timestamp) AS end, sessionid FROM logs_events WHERE sessionid = ?;";
            $sessions = $this->dbhr->preQuery($sql, [ $sessid ]);
            foreach ($sessions as $session) {
                $thisone['ip'] = $session['ip'];
                $routes = $this->dbhr->preQuery("SELECT route FROM logs_events WHERE timestamp = ? AND sessionid = ?;", [ $session['start'], $session['sessionid'] ]);
                foreach ($routes as $route) {
                    $thisone['entry'] = $route['route'];
                }

                if ($thisone['ip']) {
                    $thisone['modtools'] = strpos($session['route'], 'modtools') !== FALSE;
                    $thisone['viewx'] = $session['viewx'];
                    $thisone['viewy'] = $session['viewy'];

                    if ($session['userid']) {
                        $u = User::get($this->dbhr, $this->dbhm, $session['userid']);
                        $thisone['user'] = $u->getPublic(NULL, FALSE);
                    }

                    $thisone['start'] = ISODate($session['start']);
                    $thisone['end'] = ISODate($session['end']);

                    $ret[] = $thisone;
                }
            }
        }

        usort($ret, function($a, $b) {
            return(strtotime($b['start']) - strtotime($a['start']));
        });

        return($ret);
    }

    public function get($sessionid) {
        $events = NULL;

        # Get the first client timestamp.
        $sql = "SELECT clienttimestamp FROM logs_events WHERE sessionid = ? ORDER BY id ASC LIMIT 1;";
        #error_log("$sql, $sessionid");
        $firsts = $this->dbhr->preQuery($sql, [
            $sessionid
        ]);

        foreach ($firsts as $first) {
            $sql = "SELECT *, TIMESTAMPDIFF(MICROSECOND, ?, clienttimestamp) / 1000 AS clientdiff FROM logs_events WHERE sessionid = ? ORDER BY clienttimestamp ASC;";
            $events = $this->dbhr->preQuery($sql, [
                $first['clienttimestamp'],
                $sessionid
            ]);

            $last = null;

            # Convert the differences into relative diffs between the items.
            foreach ($events as &$item) {
                $thisone = $item['clientdiff'];

                if ($item['datasameas']) {
                    # The other one might have gone, but if so we'll just not find it.
                    $sql = "SELECT data FROM logs_events WHERE id = ?;";
                    $logs = $this->dbhr->preQuery($sql, [ $item['datasameas'] ]);
                    foreach ($logs as $log) {
                        $item['data'] = $log['data'];
                    }
                }

                if ($last) {
                    $item['clientdiff'] = floatval($item['clientdiff']) - $last;
                } else {
                    $item['clientdiff'] = floatval(0);
                }

                $last = $thisone;
            }
        }

        return($events);
    }
}