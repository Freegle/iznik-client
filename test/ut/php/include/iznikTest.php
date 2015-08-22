<?php
error_log(getcwd());
require_once '../../../include/config.php';
require_once BASE_DIR . '/include/db.php';

require_once BASE_DIR . '/composer/vendor/phpunit/phpunit/src/Framework/TestCase.php';
require_once BASE_DIR . '/composer/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class iznikTest extends PHPUnit_Framework_TestCase {
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
}

