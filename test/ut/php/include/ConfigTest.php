<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/config/ModConfig.php';
require_once IZNIK_BASE . '/include/config/StdMessage.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class configTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup1'");

        $this->user = new User($this->dbhm, $this->dbhm);
        $this->uid = $this->user->create('Test', 'User', NULL);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        # Basic create
        $c = new ModConfig($this->dbhr, $this->dbhm);
        $id = $c->create('TestConfig');
        assertNotNull($id);
        $c = new ModConfig($this->dbhr, $this->dbhm, $id);
        assertNotNull($c);

        # Use on a group
        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1, User::ROLE_MODERATOR);
        $c->useOnGroup($uid, $group1);
        assertEquals($id, $c->getForGroup($uid, $group1));

        $m = new StdMessage($this->dbhr, $this->dbhm);
        $mid = $m->create("TestStdMessage", $id);
        assertNotNull($mid);
        $m = new StdMessage($this->dbhr, $this->dbhm, $mid);

        assertEquals('TestConfig', $c->getPublic()['name']);
        assertEquals('TestStdMessage', $c->getPublic()['stdmsgs'][0]['title']);

        $m->delete();
        $c->delete();

        # Create as current user
        assertTrue($this->user->login('testpw'));
        $id = $c->create('TestConfig');
        assertNotNull($id);
        $c = new ModConfig($this->dbhr, $this->dbhm, $id);
        assertNotNull($c);
        assertEquals($this->uid, $c->getPrivate('createdby'));

        $c->delete();

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('preExec', 'preQuery'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());

        $c = new ModConfig($this->dbhr, $this->dbhm);
        $c->setDbhm($mock);
        $id = $c->create('TestConfig');
        assertNull($id);

        $mock->method('preQuery')->willThrowException(new Exception());
        $id = $c->create('TestConfig');
        assertNull($id);

        $c = new StdMessage($this->dbhr, $this->dbhm);
        $c->setDbhm($mock);
        $id = $c->create('TestStd', $id);
        assertNull($id);

        error_log(__METHOD__ . " end");
    }
}

