<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/message/Message.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class notificationsTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        error_log("Created $id");

        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('uthook'))
            ->getMock();
        $mock->method('uthook')->willThrowException(new Exception());

        $n = new Notifications($this->dbhr, $this->dbhm);
        $n->add($id, Notifications::PUSH_GOOGLE, 'test');
        assertEquals(0, $mock->notify($id));
        $n->add($id, Notifications::PUSH_FIREFOX, 'test2');
        assertEquals(1, $n->notify($id));

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        error_log("Created $id");

        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('fsockopen'))
            ->getMock();
        $mock->method('fsockopen')->willThrowException(new Exception());
        $mock->poke($id, [ 'ut' => 1 ]);

        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('fputs'))
            ->getMock();
        $mock->method('fsockopen')->willThrowException(new Exception());
        $mock->poke($id, [ 'ut' => 1 ]);

        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('fsockopen'))
            ->getMock();
        $mock->method('fsockopen')->willReturn(NULL);
        $mock->poke($id, [ 'ut' => 1 ]);

        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('puts'))
            ->getMock();
        $mock->method('fsockopen')->willReturn(NULL);
        $mock->poke($id, [ 'ut' => 1 ]);
        
        error_log(__METHOD__ . " end");
    }
}

