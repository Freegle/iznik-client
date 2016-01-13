<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';
require_once(IZNIK_BASE . '/include/config/ModConfig.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class configAPITest extends IznikAPITest {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM mod_configs WHERE name LIKE 'UTTest%';");

        # Create a moderator and log in as them
        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($this->groupid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testCreate() {
        error_log(__METHOD__);

        # Get invalid id
        $ret = $this->call('modconfig', 'GET', [
            'id' => -1
        ]);
        assertEquals(2, $ret['ret']);

        # Create when not logged in
        $ret = $this->call('modconfig', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(1, $ret['ret']);

        # Create without name
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('modconfig', 'POST', [
        ]);
        assertEquals(3, $ret['ret']);

        # Create as member
        $ret = $this->call('modconfig', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(4, $ret['ret']);

        # Create as moderator
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('modconfig', 'POST', [
            'name' => 'UTTest2'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        $ret = $this->call('modconfig', 'GET', [
            'id' => $id
        ]);
        error_log("Returned " . var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['config']['id']);
        assertEquals($this->uid, $ret['config']['createdby']);

        error_log(__METHOD__ . " end");
    }

    public function testPatch() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('modconfig', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        # Log out
        unset($_SESSION['id']);

        # When not logged in
        $ret = $this->call('modconfig', 'PATCH', [
            'id' => $id
        ]);
        assertEquals(1, $ret['ret']);

        # Log back in
        assertTrue($this->user->login('testpw'));

        # As a non-mod
        error_log("Demote");
        $this->user->setRole(User::ROLE_MEMBER, $this->groupid);
        $ret = $this->call('modconfig', 'PATCH', [
            'id' => $id,
            'name' => 'UTTest2'
        ]);
        assertEquals(4, $ret['ret']);

        # Promote back
        $this->user->setRole(User::ROLE_OWNER, $this->groupid);
        $ret = $this->call('modconfig', 'PATCH', [
            'id' => $id,
            'name' => 'UTTest2'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('modconfig', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals('UTTest2', $ret['config']['name']);

        # Try as a mod, but the wrong one.
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup2', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $user = new User($this->dbhr, $this->dbhm, $uid);
        $user->addEmail('test2@test.com');
        $user->addMembership($gid, User::ROLE_OWNER);
        assertGreaterThan(0, $user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($user->login('testpw'));

        $ret = $this->call('modconfig', 'PATCH', [
            'id' => $id,
            'name' => 'UTTest3'
        ]);
        assertEquals(4, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testDelete() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('modconfig', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        # Log out
        unset($_SESSION['id']);

        # When not logged in
        $ret = $this->call('modconfig', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(1, $ret['ret']);

        # Log back in
        assertTrue($this->user->login('testpw'));

        # As a non-mod
        error_log("Demote");
        $this->user->setRole(User::ROLE_MEMBER, $this->groupid);
        $ret = $this->call('modconfig', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(4, $ret['ret']);

        # Try as a mod, but the wrong one.
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup2', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $user = new User($this->dbhr, $this->dbhm, $uid);
        $user->addEmail('test2@test.com');
        $user->addMembership($gid, User::ROLE_OWNER);
        assertGreaterThan(0, $user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($user->login('testpw'));

        $ret = $this->call('modconfig', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(4, $ret['ret']);

        # Promote back
        $this->user->setRole(User::ROLE_OWNER, $this->groupid);
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('modconfig', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('modconfig', 'GET', [
            'id' => $id
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

