<?php
require_once dirname(__FILE__) . '/../../include/config.php';
require_once IZNIK_BASE . '/include/db.php';

require_once IZNIK_BASE . '/composer/vendor/phpunit/phpunit/src/Framework/TestCase.php';
require_once IZNIK_BASE . '/composer/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class IznikTest extends PHPUnit_Framework_TestCase {
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

