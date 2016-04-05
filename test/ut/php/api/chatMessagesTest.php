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

        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_FREEGLE);

        $c = new ChatRoom($this->dbhr, $this->dbhm);
        $this->cid = $c->create('test', $this->groupid);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testGet()
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

    public function testPut()
    {
        error_log(__METHOD__);

        # Logged out - no rooms
        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test' ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid));
        assertTrue($this->user->login('testpw'));

        # Now we're talking.
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
}
