<?php
#
#  This script handles less critical background tasks.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
global $dbhr, $dbhm;

use Pheanstalk\Pheanstalk;

$lock = "/tmp/iznik_background.lock";
$lockh = fopen($lock, 'a');

try {
    $block = 0;

    if (flock($lockh, LOCK_EX | LOCK_NB, $block)) {
        $pheanstalk = new Pheanstalk('127.0.0.1');

        while (true) {
            $job = $pheanstalk->reserve();
            error_log("Reserved " . $job->getId());

            # We have got one job.  Get any others.
            #
            # We want to pull a lot of jobs off the queue.  This is because for ones which are SQL, it is
            # more efficient to perform them in a single request.
            $sqls = array();
            $count = 0;

            do {
                if ($job) {
                    try {
                        $count++;
                        $data = json_decode($job->getData(), true);

                        switch ($data['type']) {
                            case 'sql': {
                                $sqls[] = $data['sql'];
                                break;
                            }

                            default: {
                                error_log("Unknown job type {$data['type']} " . var_export($data, TRUE));
                            }
                        }
                    } catch (Exception $e) {}

                    # Whatever it is, we need to delete the job to avoid getting stuck.
                    $rc = $pheanstalk->delete($job);
                }

                $job = $pheanstalk->reserve(0);
                error_log("Reserved " . $job->getId());
            } while ($job && $count < 100);

            if (count($sqls) > 0) {
                try {
                    $sql = implode($sqls);
                    $rc = $dbhm->exec($sql, FALSE);
                } catch (Exception $e) {
                    $msg = $e->getMessage();

                    if (strpos($e, 'gone away')) {
                        # SQL server has gone away.  Exit - cron will restart and we'll get new handles.
                        error_log("SQL gone away - exit");
                        exit(1);
                    }

                    error_log("SQL exception " . var_export($e, true));
                }

                if (count($sqls) < 5) {
                    # We didn't get many.  Sleep a bit to let them build up.
                    #error_log("Not many - sleep");
                    sleep(1);
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Top-level exception " . $e->getMessage() . "\n");
}

flock($lockh, LOCK_UN);
fclose($lockh);