<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/Rooms.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class chatRoomsTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM chat_rooms WHERE name = 'test';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testGroup() {
        error_log(__METHOD__);

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createGroupChat('test', NULL);
        assertNotNull($id);

        $r->setAttributes(['name' => 'test']);
        assertEquals('test', $r->getPublic()['name']);
        
        assertEquals(1, $r->delete());

        error_log(__METHOD__ . " end");
    }

    public function testConversation() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $u1 = $u->create(NULL, NULL, "Test User 1");
        $u->addEmail('test1@test.com');
        $u2 = $u->create(NULL, NULL, "Test User 2");
        $u->addEmail('test2@test.com');

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createConversation($u1, $u2);
        assertNotNull($id);

        # Further creates should find the same one.
        $id2 = $r->createConversation($u1, $u2);
        assertEquals($id, $id2);

        $id2 = $r->createConversation($u2, $u1);
        assertEquals($id, $id2);

        assertEquals(1, $r->delete());

        error_log(__METHOD__ . " end");
    }

    public function testError() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $r->setDbhm($mock);

        $id = $r->createGroupChat('test');
        assertNull($id);

        error_log(__METHOD__ . " end");
    }
}


