<?php
require_once 'IznikTest.php';
require_once BASE_DIR . '/include/IncomingMessage.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class IncomingMessageTest extends IznikTest {
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

    public function testBasic() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse($msg);
        assertEquals('Basic test', $m->getSubject());
        assertEquals('Edward Hibbert', $m->getFrom()[0]['display']);
        assertEquals('edward@ehibbert.org.uk', $m->getFrom()[0]['address']);
        assertEquals('freegleplayground@yahoogroups.com', $m->getTo()[0]['address']);

        error_log(__METHOD__ . " end");
    }
}

