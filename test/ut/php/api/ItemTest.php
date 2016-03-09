<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class itemAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM items WHERE name LIKE 'UTTest%';");

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
        $ret = $this->call('item', 'GET', [
            'id' => -1
        ]);
        assertEquals(2, $ret['ret']);

        # Create when not logged in
        $ret = $this->call('item', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(1, $ret['ret']);

        # Create without name
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('item', 'POST', [
        ]);
        assertEquals(3, $ret['ret']);

        # Create as member
        $ret = $this->call('item', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(4, $ret['ret']);

        # Create as moderator
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('item', 'POST', [
            'name' => 'UTTest',
            'dup' => 2
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        $ret = $this->call('item', 'GET', [
            'id' => $id
        ]);
        error_log("Returned " . var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['item']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testPatch() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('item', 'POST', [
            'name' => 'UTTest'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created $id");

        # Log out
        unset($_SESSION['id']);

        # When not logged in
        $ret = $this->call('item', 'PATCH', [
            'id' => $id
        ]);
        assertEquals(1, $ret['ret']);

        # Log back in
        assertTrue($this->user->login('testpw'));

        # As a non-mod
        error_log("Demote");
        $this->user->setRole(User::ROLE_MEMBER, $this->groupid);
        $ret = $this->call('item', 'PATCH', [
            'id' => $id,
            'name' => 'UTTest2'
        ]);
        assertEquals(4, $ret['ret']);

        # Promote back
        $this->user->setRole(User::ROLE_OWNER, $this->groupid);
        $ret = $this->call('item', 'PATCH', [
            'id' => $id,
            'name' => 'UTTest2'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('item', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals('UTTest2', $ret['item']['name']);

        error_log(__METHOD__ . " end");
    }

    public function testDelete() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('item', 'POST', [
            'name' => 'UTTest',
            'dup' => time() . $this->count++
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        # Log out
        unset($_SESSION['id']);

        # When not logged in
        $ret = $this->call('item', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(1, $ret['ret']);

        # Log back in
        assertTrue($this->user->login('testpw'));

        # As a non-mod
        error_log("Demote");
        $this->user->setRole(User::ROLE_MEMBER, $this->groupid);
        $ret = $this->call('item', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(4, $ret['ret']);

        # Promote back
        $this->user->setRole(User::ROLE_OWNER, $this->groupid);
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('item', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('item', 'GET', [
            'id' => $id
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testTypeahead() {
        error_log(__METHOD__);

        # Get invalid id
        $ret = $this->call('item', 'GET', [
            'typeahead' => 'sof'
        ]);
        assertEquals(0, $ret['ret']);
        error_log(var_export($ret, TRUE));

        error_log(__METHOD__ . " end");
    }
}

