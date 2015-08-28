<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class userTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_emails WHERE email = 'test@test.com');");
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
        $atts = $u->getPublic();
        assertEquals('Test', $atts['firstname']);
        assertEquals('User', $atts['lastname']);
        assertNull($atts['fullname']);
        assertEquals('Test User', $u->getName());
        assertEquals($id, $u->getPrivate('id'));
        assertNull($u->getPrivate('invalidid'));
        assertGreaterThan(0, $u->delete());

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $atts = $u->getPublic();
        assertNull($atts['firstname']);
        assertNull($atts['lastname']);
        assertEquals('Test User', $atts['fullname']);
        assertEquals('Test User', $u->getName());
        assertEquals($id, $u->getPrivate('id'));
        assertGreaterThan(0, $u->delete());

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $u = new User($this->dbhr, $this->dbhm);
        $u->setDbhm($mock);
        $id = $u->create(NULL, NULL, 'Test User');
        assertNull($id);

        error_log(__METHOD__ . " end");
    }


    public function testEmails() {
        error_log(__METHOD__);

        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertEquals(0, count($u->getEmails()));

        # Add an email - should work.
        assertGreaterThan(0, $u->addEmail('test@test.com'));

        # Check it's there
        $emails = $u->getEmails();
        assertEquals(1, count($emails));
        assertEquals('test@test.com', $emails[0]['email']);

        # Add it again - should fail
        assertEquals(0, $u->addEmail('test@test.com'));

        assertEquals($id, $u->findByEmail('test@test.com'));
        assertNull($u->findByEmail('testinvalid@test.com'));

        error_log(__METHOD__ . " end");
    }
}

