<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/ChatRoom.php';

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

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_FREEGLE);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testUser2User()
    {
        error_log(__METHOD__);
        
        # Logged out - no rooms
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');

        $c = new ChatRoom($this->dbhr, $this->dbhm);
        $rid = $c->createConversation($this->uid, $uid);

        # Just because it exists, doesn't mean we should be able to see it.
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        assertTrue($this->user->login('testpw'));

        # Now we're talking.
        $ret = $this->call('chatrooms', 'GET', [
            'chattypes' => [ ChatRoom::TYPE_USER2USER ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($rid, $ret['chatrooms'][0]['id']);
        assertEquals('Test User', $ret['chatrooms'][0]['name']);

        $ret = $this->call('chatrooms', 'GET', [
            'id' => $rid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($rid, $ret['chatroom']['id']);

        $ret = $this->call('chatrooms', 'POST', [
            'id' => $rid,
            'lastmsgseen' => 2,
        ]);

        $ret = $this->call('chatrooms', 'GET', [
            'id' => $rid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($rid, $ret['chatroom']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testMod2Mod()
    {
        error_log(__METHOD__);

        # Logged out - no rooms
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        $c = new ChatRoom($this->dbhr, $this->dbhm);
        $rid = $c->createGroupChat('test', $this->groupid);

        # Just because it exists, doesn't mean we should be able to see it.
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        assertTrue($this->user->login('testpw'));

        # Still not, even logged in.
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertFalse(pres('chatrooms', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));

        # Now we're talking.
        $ret = $this->call('chatrooms', 'GET', [
            'chattypes' => [ ChatRoom::TYPE_MOD2MOD ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($rid, $ret['chatrooms'][0]['id']);
        assertEquals('testgroup Mods', $ret['chatrooms'][0]['name']);

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
        assertEquals('Online', $ret['roster'][0]['status']);

        $ret = $this->call('chatrooms', 'POST', [
            'id' => $rid,
            'lastmsgseen' => 1
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals($this->uid, $ret['roster'][0]['userid']);
        assertEquals('Test User', $ret['roster'][0]['user']['fullname']);

        error_log(__METHOD__ . " end");
    }

    public function testUser2Mod()
    {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        # Create a support room from this user to the group mods
        $this->user->addMembership($this->groupid);

        $ret = $this->call('chatrooms', 'PUT', [
            'groupid' => $this->groupid,
            'chattype' => ChatRoom::TYPE_USER2MOD
        ]);
        assertEquals(0, $ret['ret']);
        $rid = $ret['id'];
        error_log("Created User2Mod $rid");
        assertNotNull($rid);
        assertFalse(pres('chatrooms', $ret));

        # Now we're talking.
        $ret = $this->call('chatrooms', 'GET', [
            'chattypes' => [ ChatRoom::TYPE_USER2USER, ChatRoom::TYPE_MOD2MOD ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['chatrooms']));

        $ret = $this->call('chatrooms', 'GET', [
            'chattypes' => [ ChatRoom::TYPE_USER2MOD ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($rid, $ret['chatrooms'][0]['id']);

        # Now create a group mod
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $u->addMembership($this->groupid);
        assertTrue($u->login('testpw'));

        # Shouldn't see it before we promote.
        $ret = $this->call('chatrooms', 'GET', [
            'chattypes' => [ ChatRoom::TYPE_USER2MOD ]
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['chatrooms']));

        # Now promote.
        $u->setRole(USer::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('chatrooms', 'GET', [
            'chattypes' => [ ChatRoom::TYPE_USER2MOD ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($rid, $ret['chatrooms'][0]['id']);

        error_log(__METHOD__ . " end");
    }
}
