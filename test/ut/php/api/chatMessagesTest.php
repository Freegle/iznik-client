<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/Rooms.php';
require_once IZNIK_BASE . '/include/chat/Messages.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class chatMessagesAPITest extends IznikAPITestCase
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

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid2 = $u->create(NULL, NULL, 'Test User');
        $this->user2 = new User($this->dbhr, $this->dbhm, $this->uid2);
        assertGreaterThan(0, $this->user2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid3 = $u->create(NULL, NULL, 'Test User');
        $this->user3 = new User($this->dbhr, $this->dbhm, $this->uid3);
        assertGreaterThan(0, $this->user3->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_FREEGLE);

        $c = new ChatRoom($this->dbhr, $this->dbhm);
        $this->cid = $c->createGroupChat('test', $this->groupid);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testGroupGet()
    {
        error_log(__METHOD__);

        # Logged out - no rooms
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        $m = new ChatMessage($this->dbhr, $this->dbhm);;
        $mid = $m->create($this->cid, $this->uid, 'Test');

        # Just because it exists, doesn't mean we should be able to see it.
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertTrue($this->user->login('testpw'));

        # Still not, even logged in.
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(2, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid));

        # Now we're talking.
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatmessages']));
        assertEquals($mid, $ret['chatmessages'][0]['id']);
        assertEquals($this->cid, $ret['chatmessages'][0]['chatid']);
        assertEquals('Test', $ret['chatmessages'][0]['message']);

        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid,
            'id' => $mid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($mid, $ret['chatmessage']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testGroupPut()
    {
        error_log(__METHOD__);

        # Logged out - no rooms
        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test' ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid));
        assertTrue($this->user->login('testpw'));

        # Now we're talking.  Make sure we're on the roster.
        $ret = $this->call('chatrooms', 'POST', [
            'id' => $this->cid,
            'lastmsgseen' => 1
        ]);

        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test' ]);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid = $ret['id'];

        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid,
            'id' => $mid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($mid, $ret['chatmessage']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testConversation() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        # Create a chat to the second user.
        $ret = $this->call('chatrooms', 'PUT', [
            'userid' => $this->uid2
        ]);

        assertEquals(0, $ret['ret']);
        $this->cid = $ret['id'];
        assertNotNull($this->cid);

        $ret = $this->call('chatrooms', 'GET', []);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($this->cid, $ret['chatrooms'][0]['id']);

        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test' ]);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid1 = $ret['id'];

        # Now log in as the other user.
        assertTrue($this->user2->login('testpw'));

        # Should be able to see the room
        $ret = $this->call('chatrooms', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($this->cid, $ret['chatrooms'][0]['id']);

        # If we create a chat to the first user, should get the same chat
        $ret = $this->call('chatrooms', 'PUT', [
            'userid' => $this->uid
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals($this->cid, $ret['id']);

        # Should see the messages
        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid,
            'id' => $mid1
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($mid1, $ret['chatmessage']['id']);

        # Should be able to post
        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test' ]);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);

        # Now log in as a third user
        assertTrue($this->user3->login('testpw'));

        # Shouldn't see the chat
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(2, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        # Shouldn't see the messages
        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid,
            'id' => $mid1
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}
