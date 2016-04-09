<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/Rooms.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class chatRoomsAPITest extends IznikAPITestCase
{
    public $dbhr, $dbhm;

    protected function setUp()
    {
        parent::setUp();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM chat_rooms WHERE name = 'test';");

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_FREEGLE);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testBasic()
    {
        error_log(__METHOD__);
        
        # Logged out - no rooms
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));
        
        $c = new ChatRoom($this->dbhr, $this->dbhm);
        $rid = $c->create('test', $this->groupid);

        # Just because it exists, doesn't mean we should be able to see it.
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        assertTrue($this->user->login('testpw'));

        # Still not, even logged in.
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid));

        # Now we're talking.
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($rid, $ret['chatrooms'][0]['id']);
        assertEquals('test', $ret['chatrooms'][0]['name']);

        $ret = $this->call('chatrooms', 'GET', [
            'id' => $rid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($rid, $ret['chatroom']['id']);

        # Roster
        $ret = $this->call('chatrooms', 'POST', [
            'id' => $rid,
            'lastmsgseen' => 1
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals($this->uid, $ret['roster'][0]['userid']);
        assertEquals('Test User', $ret['roster'][0]['user']['fullname']);

        error_log(__METHOD__ . " end");
    }
}
