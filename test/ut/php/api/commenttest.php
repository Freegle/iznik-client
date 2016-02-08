<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class commentAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM mod_configs WHERE name LIKE 'UTTest%';");

        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($this->groupid, User::ROLE_OWNER);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($this->user->login('testpw'));

        $this->uid2 = $u->create(NULL, NULL, 'Test User');
        $this->user2 = new User($this->dbhr, $this->dbhm, $this->uid2);
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBase() {
        error_log(__METHOD__);

        $ret = $this->call('comment', 'POST', [
            'userid' => $this->uid2,
            'groupid' => $this->groupid,
            'user1' => 'Test comment'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        assertNotNull($id);

        $ret = $this->call('comment', 'GET', [
            'id' => 0
        ]);
        assertEquals(2, $ret['ret']);

        $ret = $this->call('comment', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($this->uid, $ret['comment']['byuserid']);
        assertEquals('Test comment', $ret['comment']['user1']);

        $ret = $this->call('comment', 'PUT', [
            'id' => $id,
            'user1' => 'Test comment2'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('comment', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($this->uid, $ret['comment']['byuserid']);
        assertEquals('Test comment2', $ret['comment']['user1']);

        $ret = $this->call('comment', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

}

