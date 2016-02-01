<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/session/Yahoo.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class yahooTest extends IznikTest {
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

    public function testException() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $y = Yahoo::getInstance($this->dbhr, $this->dbhm);
        $rc  = $y->login();
        assertEquals(1, $rc[1]['ret']);
        assertTrue(array_key_exists('redirect', $rc[1]));

        $email = 'test' . microtime() . '@test.com';
        $mock = $this->getMockBuilder('LightOpenID')
            ->disableOriginalConstructor()
            ->setMethods(array('validate', 'getAttributes'))
            ->getMock();
        $mock->method('validate')->willReturn(true);
        $mock->method('getAttributes')->willThrowException(new Exception());
        $y->setOpenid($mock);

        # Login first time - should work
        list($session, $ret) = $y->login();
        assertNull($session);

        error_log(__METHOD__ . " end");
    }

    public function testBasic() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';

        # Check singleton
        $y = Yahoo::getInstance($this->dbhr, $this->dbhm);
        assertEquals($y, Yahoo::getInstance($this->dbhr, $this->dbhm));

        $rc  = $y->login();
        assertEquals(1, $rc[1]['ret']);
        assertTrue(array_key_exists('redirect', $rc[1]));

        $email = 'test' . microtime() . '@test.com';
        $mock = $this->getMockBuilder('LightOpenID')
            ->disableOriginalConstructor()
            ->setMethods(array('validate', 'getAttributes'))
            ->getMock();
        $mock->method('validate')->willReturn(true);
        $mock->method('getAttributes')->willReturn([
            'contact/email' => $email,
            'namePerson' => 'Test User'
        ]);
        $y->setOpenid($mock);

        # Login first time - should work
        list($session, $ret) = $y->login();
        $id = $session->getId();
        assertNotNull($session);
        assertEquals(0, $ret['ret']);

        # Login again - should also work
        $this->dbhm->preExec("UPDATE users SET fullname = NULL WHERE id = $id;");
        list($session, $ret) = $y->login();
        assertNotNull($session);
        assertEquals(0, $ret['ret']);

        # Create another user and move the email over to simulate a duplicate
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        error_log("Users $id and $uid");
        $rc = $this->dbhm->preExec("UPDATE users_emails SET userid = $uid WHERE userid = $id;");
        assertEquals(1, $rc);
        list($session, $ret) = $y->login();
        assertNotNull($session);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

