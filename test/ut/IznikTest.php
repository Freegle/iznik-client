<?php
use Pheanstalk\Pheanstalk;
require_once dirname(__FILE__) . '/../../include/config.php';
require_once IZNIK_BASE . '/include/db.php';

require_once IZNIK_BASE . '/composer/vendor/phpunit/phpunit/src/Framework/TestCase.php';
require_once IZNIK_BASE . '/composer/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class IznikTest extends PHPUnit_Framework_TestCase {
    const LOG_SLEEP=30;

    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function waitBackground() {
        $pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
        $count = 0;
        do {
            $stats = $pheanstalk->stats();
            $ready = $stats['current-jobs-ready'];

            if ($ready == 0) {
                break;
            }

            error_log("...waiting for background work, current $ready, try $count");
            sleep(1);
            $count++;

        } while ($count < IznikTest::LOG_SLEEP);

        if ($count >= IznikTest::LOG_SLEEP) {
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

        error_log("Failed to find log $type $subtype");
        return(NULL);
    }
}

