<?php
#
#  This script handles less critical background tasks.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
require_once(IZNIK_BASE . '/include/session/Facebook.php');
global $dbhr, $dbhm;

use Pheanstalk\Pheanstalk;

$lockh = lockScript(basename(__FILE__));

$dbhm->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);


try {
    $pheanstalk = new Pheanstalk('127.0.0.1');

    while (true) {
        $job = $pheanstalk->reserve();

        # We have got one job.  Get any others.
        #
        # We want to pull a lot of jobs off the queue.  This is because for ones which are SQL, it is
        # more efficient to perform them in a single request.
        $sqls = [];
        $events = [];
        $count = 0;

        do {
            if ($job) {
                $exit = FALSE;

                try {
                    $count++;
                    $data = json_decode($job->getData(), true);

                    switch ($data['type']) {
                        case 'sql': {
                            $sqls[] = $data['sql'];
                            break;
                        }

                        case 'sqlfile': {
                            $sqls[] = file_get_contents($data['file']);
                            unlink($data['file']);
                            break;
                        }

                        case 'events': {
                            $events = array_merge($events, $data['events']);
                            break;
                        }

                        case 'webpush': {
                            $n = new Notifications($dbhr, $dbhm);
                            $n->executeSend($data['userid'], $data['params'], $data['endpoint'], $data['payload']);
                            break;
                        }

                        case 'facebooknotif': {
                            $n = new Facebook($dbhr, $dbhm);
                            $n->executeNotify($data['fbid'], $data['message'], $data['href']);
                            break;
                        }

                        case 'exit': {
                            error_log("Asked to exit");
                            $exit = TRUE;
                            break;
                        }

                        default: {
                            error_log("Unknown job type {$data['type']} " . var_export($data, TRUE));
                        }
                    }
                } catch (Exception $e) { error_log("Exception " . $e->getMessage()); }

                # Whatever it is, we need to delete the job to avoid getting stuck.
                $rc = $pheanstalk->delete($job);

                if ($exit) {
                    exit(0);
                }
            }

            $job = $pheanstalk->reserve(0);
        } while ($job && $count < 100);

        if (count($sqls) > 0) {
            try {
                $sql = implode($sqls);
                $rc = $dbhm->exec($sql, FALSE);
            } catch (Exception $e) {
                # Something awry in this batch.  Do them one by one to reduce the number we lose.
                error_log("Batch failed, one at a time");
                foreach ($sqls as $sql) {
                    try {
                        $dbhm->exec($sql, FALSE);
                    } catch (Exception $e) {
                        $msg = $e->getMessage();

                        if (strpos($e, 'gone away')) {
                            # SQL server has gone away.  Exit - cron will restart and we'll get new handles.
                            error_log("SQL gone away - exit");
                            exit(1);
                        }

                        error_log("SQL exception " . var_export($e, TRUE));
                    }
                }
            }

            if (count($sqls) < 5) {
                # We didn't get many.  Sleep a bit to let them build up.
                error_log("Not many - sleep");
                sleep(1);
            }
        }

        if (count($events) > 0) {
            # There are a lot of events, so we want to make sure we insert them efficiently.  We can't use
            # LOAD DATA INFILE as we might not be on the same machine as the MySQL server.
            $atts = ['userid', 'sessionid', 'route', 'target', 'event', 'posx', 'posy', 'viewx', 'viewy', 'data', 'datasameas', 'ip'];

            $sql = "INSERT INTO logs_events (" . implode(',', $atts) . ", timestamp, clienttimestamp) VALUES ";
            $first = TRUE;
            foreach ($events as $event) {
                if (!$first) {
                    $sql .= ', ';
                }

                $first = FALSE;
                $sql .= ' (';
                foreach ($atts as $att) {
                    $sql .= (pres($att, $event) ? $dbhm->quote($event[$att]) : 'NULL') . ", ";
                }

                $sql .= "CURTIME(3), " . (pres('clienttimestamp', $event) ? "FROM_UNIXTIME({$event['clienttimestamp']})" : "CURTIME(3)") . ")";
            }

            $sql .= ";";

            #error_log(count($events) . " events");

            $dbhm->exec($sql, FALSE);
        }
    }
} catch (Exception $e) {
    error_log("Top-level exception " . $e->getMessage() . "\n");
}

unlockScript($lockh);