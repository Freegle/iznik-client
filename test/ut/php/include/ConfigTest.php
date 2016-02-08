<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/config/ModConfig.php';
require_once IZNIK_BASE . '/include/config/StdMessage.php';
require_once IZNIK_BASE . '/include/config/BulkOp.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class configTest extends IznikTestCase {
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
        $c->setPrivate('default', TRUE);
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

        assertTrue($this->user->login('testpw'));
        $configs = $this->user->getConfigs();
        $found = FALSE;
        foreach ($configs as $config) {
            if ($config['id'] == $id) {
                $found = TRUE;
                assertEquals(ModConfig::CANSEE_DEFAULT, $config['cansee']);
            }
        }
        assertTrue($found);
        unset($_SESSION['id']);

        # Another mod on this group with no config set up should pick this one up as shared.
        $c->setPrivate('default', FALSE);
        $uid2 = $u->create(NULL, NULL, 'Test User');
        $u2 = new User($this->dbhr, $this->dbhm, $uid2);
        assertGreaterThan(0, $u2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $u2->addMembership($group1, User::ROLE_OWNER);
        assertEquals($id, $c->getForGroup($uid, $group1));
        assertEquals($id, $c->getForGroup($uid2, $group1));

        assertTrue($u2->login('testpw'));
        $configs = $u2->getConfigs();
        $found = FALSE;
        foreach ($configs as $config) {
            if ($config['id'] == $id) {
                $found = TRUE;
                assertEquals(ModConfig::CANSEE_SHARED, $config['cansee']);
            }
        }
        assertTrue($found);
        unset($_SESSION['id']);

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

        $configs = $this->user->getConfigs();

        # Have to scan as there are defaults.
        $found = FALSE;
        foreach ($configs as $config) {
            if ($id == $config['id']) {
                $found = TRUE;
            }
        }
        assertTrue($found);

        # Sleep for background logging
        $this->waitBackground();

        $logs = $this->user->getPublic(NULL, FALSE, TRUE)['logs'];
        $log = $this->findLog(Log::TYPE_CONFIG, Log::SUBTYPE_CREATED, $logs);
        assertEquals($this->uid, $log['byuser']['id']);

        # Copy
        $m = new StdMessage($this->dbhr, $this->dbhm);
        $sid1 = $m->create("TestStdMessage1", $id);
        $sid2 = $m->create("TestStdMessage2", $id);
        $m = new StdMessage($this->dbhr, $this->dbhm, $sid1);
        $m->setPrivate('action', 'Approve');
        $id2 = $c->create('TestConfig (Copy)', $this->user->getId(), $id);
        error_log("Copied $id to $id2");
        $c = new ModConfig($this->dbhr, $this->dbhm, $id);
        $c2 = new ModConfig($this->dbhr, $this->dbhm, $id2);
        $oldatts = $c->getPublic();
        error_log("Old " . var_export($oldatts, true));
        $newatts = $c2->getPublic();
        error_log("New " . var_export($newatts, true));

        # Should have created a message order during the copy.
        assertNull($oldatts['messageorder']);
        assertNotNull($newatts['messageorder']);

        assertEquals('TestConfig (Copy)', $newatts['name']);
        unset($oldatts['id']);
        unset($oldatts['name']);
        unset($oldatts['messageorder']);
        unset($oldatts['stdmsgs']);

        foreach ($oldatts as $att => $val) {
            assertEquals($val, $newatts[$att]);
        }

        assertEquals('Approve', $newatts['stdmsgs'][0]['action']);

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

