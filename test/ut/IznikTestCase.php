<?php
use Pheanstalk\Pheanstalk;
require_once dirname(__FILE__) . '/../../include/config.php';
require_once IZNIK_BASE . '/include/db.php';
require_once IZNIK_BASE . '/include/user/User.php';

require_once IZNIK_BASE . '/composer/vendor/phpunit/phpunit/src/Framework/TestCase.php';
require_once IZNIK_BASE . '/composer/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
abstract class IznikTestCase extends PHPUnit_Framework_TestCase {
    const LOG_SLEEP = 600;
    const YAHOO_PATIENCE = 600;

    private $dbhr, $dbhm;

    public static $unique = 1;

    public function tidy() {
        $this->dbhm->preExec("DELETE FROM messages WHERE fromaddr = ?;", ['test@test.com' ]);
        $this->dbhm->preExec("DELETE FROM messages WHERE fromaddr = ?;", ['sender@example.net' ]);
        $this->dbhm->preExec("DELETE FROM messages WHERE fromaddr = ? OR fromip = ?;", ['from@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE fromaddr = ? OR fromip = ?;", ['from@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE prunedsubject LIKE ?;", ['Test spam mail']);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE prunedsubject LIKE ?;", ['Basic test']);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE prunedsubject LIKE 'OFFER: Test%';");
        $this->dbhm->preExec("DELETE FROM messages_history WHERE fromaddr IN (?,?,?) OR fromip = ?;", ['test@test.com', 'GTUBE1.1010101@example.net', 'to@test,com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM groups WHERE nameshort LIKE 'testgroup%';", []);
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';", []);
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'test@test.com';", []);
        $this->dbhm->preExec("DELETE users, users_emails FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE users_emails.backwards LIKE 'moctset%';");
        $this->dbhm->preExec("DELETE FROM messages WHERE messageid = ?;", [ 'emff7a66f1-e0ed-4792-b493-17a75d806a30@edward-x1' ]);
        $this->dbhm->preExec("DELETE FROM messages WHERE messageid = ?;", [ 'em01169273-046c-46be-b8f7-69ad036067d0@edward-x1' ]);
        $this->dbhm->preExec("DELETE FROM messages WHERE messageid = ?;", [ 'em47d9afc0-8c92-4fc8-b791-f63ff69360a2@edward-x1' ]);
        $this->dbhm->preExec("DELETE FROM messages WHERE messageid = ?;", [ 'GTUBE1.1010101@example.net' ]);
        $this->dbhm->preExec("DELETE FROM users WHERE yahooUserId = '1';");
        $this->dbhm->preExec("DELETE FROM users WHERE firstname = 'Test' AND lastname = 'User';");
        $this->dbhm->preExec("DELETE FROM users_push_notifications WHERE subscription = 'Test';");
        $this->dbhm->preExec("DELETE FROM users_emails WHERE users_emails.backwards LIKE 'moctset%';");
        $this->dbhm->preExec("DELETE FROM users_emails WHERE userid IS NULL;");
        $this->dbhm->preExec("DELETE FROM messages WHERE fromip = '4.3.2.1';");

        if (defined('_SESSION')) {
            unset($_SESSION['id']);
        }
    }

    protected function setUp() {
        parent::setUp ();

        error_reporting(E_ALL);

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->tidy();

        @session_destroy();
        @session_start();

        # Clear duplicate protection.
        $datakey = 'POST_DATA_' . session_id();
        $predis = new Redis();
        $predis->pconnect(REDIS_CONNECT);
        $predis->del($datakey);

        User::clearCache();

        set_time_limit(600);
    }

    protected function tearDown() {
        parent::tearDown ();
        try {
            $this->dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
            $this->dbhm->preExec("DELETE FROM messages WHERE subject = 'OFFER: a thing (Tuvalu)';");
            $this->dbhm->preExec("DELETE FROM communityevents WHERE title = 'Test Event';");

            $users = $this->dbhr->preQuery("SELECT userid FROM users_emails WHERE backwards LIKE 'moctset@%';");
            foreach ($users as $user) {
                $this->dbhm->preExec("DELETE FROM users WHERE id = ?;", [ $user['userid'] ]);
            }
            @session_destroy();
        } catch (Exception $e) {
            error_log("Session exception " . $e->getMessage());
        }
    }

    public function __construct() {
    }

    public function unique($msg) {

        $unique = time() . rand(1,1000000) . IznikTestCase::$unique++;
        $newmsg1 = preg_replace('/X-Yahoo-Newman-Id: (.*)\-m\d*/i', "X-Yahoo-Newman-Id: $1-m$unique", $msg);
        #assertNotEquals($msg, $newmsg1, "Newman-ID");
        $newmsg2 = preg_replace('/Message-Id:.*\<.*\>/i', 'Message-Id: <' . $unique . "@test>", $newmsg1);
        #assertNotEquals($newmsg2, $newmsg1, "Message-Id");
        #error_log("Unique $newmsg2");
        return($newmsg2);
    }

    public function waitBackground() {
        # We wait until either the queue is empty, or the first item on it has been put there since we started
        # waiting (and therefore anything we put on has been handled).
        $start = time();

        $pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
        $count = 0;
        do {
            $stats = $pheanstalk->stats();
            $ready = $stats['current-jobs-ready'];

            error_log("...waiting for background work, current $ready, try $count");

            if ($ready == 0) {
                # The background processor might have removed the job, but not quite yet processed the SQL.
                sleep(2);
                break;
            }

            try {
                $job = $pheanstalk->peekReady();
                $data = json_decode($job->getData(), true);

                if ($data['queued'] > $start) {
                    sleep(2);
                    break;
                }
            } catch (Exception $e) {}

            sleep(5);
            $count++;

        } while ($count < IznikTestCase::LOG_SLEEP);

        if ($count >= IznikTestCase::LOG_SLEEP) {
            assertFalse(TRUE, 'Failed to complete background work');
        }
    }

    public function findLog($type, $subtype, $logs) {
        foreach ($logs as $log) {
            if ($log['type'] == $type && $log['subtype'] == $subtype) {
                error_log("Found log " . var_export($log, true));
                return($log);
            }
        }

        error_log("Failed to find log $type $subtype in " . var_export($logs, TRUE));
        return(NULL);
    }
}

