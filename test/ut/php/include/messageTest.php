<?php
require_once 'iznikTest.php';
require_once BASE_DIR . '/include/message.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class messageTest extends iznikTest {
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

    public function testCreateBasic() {
        error_log(__METHOD__);

        $m = new Message(null, null);

        error_log(__METHOD__ . " end");
    }
}

