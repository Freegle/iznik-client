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
        try { @mkdir(IZNIK_BASE . '/events'); } catch (Exception $e) {};
    }

    public function record($id, $sessid, $route, $target, $action, $timestamp, $posx, $posy, $viewx, $viewy, $data) {
        # TODO POST protection will stop blatant hacks but URLs with timestamps would get through.
        $timestamp = $timestamp ? ($timestamp * 0.001) : NULL;
        $lastip = presdef('REMOTE_ADDR', $_SERVER, 'NULL');

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
            'ip' => $lastip
        ];
    }

    private function maybeClose($fh) {
        if ($fh) {
            fclose($fh);
        }
    }

    public function flush() {
        $thissess = NULL;
        $fh = NULL;

        foreach ($this->queue as $entry) {
            if (!$fh || $entry['sessionid'] != $thissess) {
                $this->maybeClose($fh);
                $fh = fopen(IZNIK_BASE . "/events/{$entry['sessionid']}", 'c');
                $thissess = $entry['sessionid'];
            }

            if ($entry['userid']) {
                # Index for users -> sessions.
                touch(IZNIK_BASE . "/events/{$entry['userid']}.{$entry['sessionid']}");
            }

            # We want to ensure we have syntactically correct JSON in the file.  That means if the file is empty,
            # we start it as an array; if not, then we overwrite the end of the array with a comma and a new entry.
            // seek to the end
            fseek($fh, 0, SEEK_END);

            if (ftell($fh) > 0)
            {
                # Already got some.
                fseek($fh, -1, SEEK_END);
                fwrite($fh, ',', 1);
                fwrite($fh, json_encode($entry) . ']');
            }
            else
            {
                # First
                fwrite($fh, json_encode([$entry]));
            }            
        }

        $this->maybeClose($fh);
        $this->queue = [];
    }
    
    public function listSessions($userid = NULL)
    {
        $fns = [];
        if ($userid) {
            # Use the index files we created.
            foreach (glob(IZNIK_BASE . "/events/$userid.*") as $filename) {
                $p = strrpos($filename, '.');
                if ($p !== FALSE) {
                    $fns[] = substr($filename, $p + 1);
                }
            }
        } else {
            # We don't have a very efficient way of getting the most recent files.
            $files = [];

            if ($handle = opendir(IZNIK_BASE . "/events")) {
                while (false !== ($file = readdir($handle))) {
                    $fn = IZNIK_BASE . "/events/$file";

                    # Skip index files.
                    if (is_file($fn) && strpos($file, '.') === FALSE) {
                        $modified = filemtime($fn);
                        $files[$modified] = $file;
                    }
                }
                closedir($handle);
            }

            krsort($files);

            foreach ($files as $mod => $file) {
                # Don't return very large sessions, which we can't really handle properly.
                if (filesize(IZNIK_BASE . "/events/$file") < 10*1024*1024) {
                    $fns[] = $file;
                    if (count($fns) > 100) { break; }
                }
            }
        }

        $ret = [];

        foreach ($fns as $fn) {
            $data = $this->get($fn);
            if (count($data) > 0) {
                $first = $data[0];
                $thisone = [
                    'id' => $fn,
                    'ip' => $first['ip'],
                    'entry' => $first['route'],
                    'viewx' => $first['viewx'],
                    'viewy' => $first['viewy'],
                    'start' => $first['clienttimestamp']
                ];

                $modtools = FALSE;
                $userid = NULL;
                $last = 0;

                foreach ($data as $d) {
                    if (strpos($d['route'], 'modtools') !== FALSE) {
                        $modtools = TRUE;
                    }

                    $userid = $userid ? $userid : $d['userid'];
                    $thistime = (new DateTime($d['clienttimestamp']))->getTimestamp();
                    $last = $thistime > $last ? $thistime : $last;
                }

                $thisone['modtools'] = $modtools;
                $thisone['end'] = ISODateFromFloat($last);

                if ($userid) {
                    $u = User::get($this->dbhr, $this->dbhm, $userid);
                    $thisone['user'] = $u->getPublic(NULL, FALSE);
                }
            }

            $ret[] = $thisone;
        }

        if (count($ret) > 0) {
            # Show most recent start date first
            usort($ret, function($a, $b) {
                $atime = (new DateTime($a['start']))->getTimestamp();
                $btime = (new DateTime($b['start']))->getTimestamp();
                return($btime - $atime);
            });
        }

        return($ret);
    }

    public function get($sessionid) {
        $events = [];
        $lasttime = NULL;

        $data = file_get_contents(IZNIK_BASE . "/events/$sessionid");
        
        if ($data) {
            $events = json_decode($data, TRUE);

            foreach ($events as &$d) {
                # Convert the differences into relative diffs between the items.
                $d['clientdiff'] = floor(($lasttime ? ($d['clienttimestamp'] - $lasttime) : 0) * 1000);
                $lasttime = $d['clienttimestamp'];
                $d['clienttimestamp'] = ISODateFromFloat($d['clienttimestamp']);
            }
        }

        return($events);
    }
}