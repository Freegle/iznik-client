<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/ChatRoom.php';
require_once IZNIK_BASE . '/include/chat/ChatMessage.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

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

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid2 = $u->create(NULL, NULL, 'Test User');
        $this->user2 = User::get($this->dbhr, $this->dbhm, $this->uid2);
        assertGreaterThan(0, $this->user2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid3 = $u->create(NULL, NULL, 'Test User');
        $this->user3 = User::get($this->dbhr, $this->dbhm, $this->uid3);
        assertGreaterThan(0, $this->user3->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_FREEGLE);

        # Recipient must be a member of at least one group
        $this->user2->addMembership($this->groupid);

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
        error_log("Created chat message $mid");

        # Just because it exists, doesn't mean we should be able to see it.
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertTrue($this->user->login('testpw'));

        # Still not, even logged in.
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        assertEquals(2, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));

        # Now we're talking.
        $ret = $this->call('chatmessages', 'GET', [ 'roomid' => $this->cid ]);
        error_log("Now we're talking " . var_export($ret, TRUE));
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
        error_log("Logged out");
        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test' ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('chatmessages', $ret));

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        assertTrue($this->user->login('testpw'));

        # Now we're talking.  Make sure we're on the roster.
        error_log("Logged in");
        $ret = $this->call('chatrooms', 'POST', [
            'id' => $this->cid,
            'lastmsgseen' => 1
        ]);

        error_log("Post test");
        $ret = $this->call('chatmessages', 'POST', [ 'roomid' => $this->cid, 'message' => 'Test2' ]);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid = $ret['id'];

        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid,
            'id' => $mid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($mid, $ret['chatmessage']['id']);

        # Test search
        $ret = $this->call('chatrooms', 'GET', [
            'search' => 'zzzz',
            'chattypes' => [ ChatRoom::TYPE_MOD2MOD ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['chatrooms']));

        $ret = $this->call('chatrooms', 'GET', [
            'search' => 'ES',
            'chattypes' => [ ChatRoom::TYPE_MOD2MOD ]
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatrooms']));
        assertEquals($this->cid, $ret['chatrooms'][0]['id']);

        error_log(__METHOD__ . " end");
    }

    public function testConversation() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        # We want to use a referenced message which is promised, to test suppressing of email notifications.
        $u = new User($this->dbhr, $this->dbhm);
        $uid2 = $u->create(NULL, NULL, 'Test User');

        $this->user2->addEmail('test@test.com');
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $refmsgid = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Promise to someone else.
        $m = new Message($this->dbhr, $this->dbhm, $refmsgid);
        $m->promise($uid2);

        # Create a chat to the second user with a referenced message from the second user.
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

        $ret = $this->call('chatmessages', 'POST', [
            'roomid' => $this->cid,
            'message' => 'Test',
            'refmsgid' => $refmsgid
        ]);
        error_log("Create message " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid1 = $ret['id'];

        # Check that the email was suppressed.
        error_log("Check for suppress of $mid1 to {$this->uid2}");
        $ret = $this->call('chatrooms', 'POST', [
            'id' => $this->cid
        ]);

        error_log(var_export($ret, TRUE));
        foreach ($ret['roster'] as $rost) {
            if ($rost['user']['id'] == $this->uid2) {
                self::assertEquals($mid1, $rost['lastmsgemailed']);
            }
        }

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
        error_log("Get message" . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals($mid1, $ret['chatmessage']['id']);

        # Should be able to post
        $ret = $this->call('chatmessages', 'POST', [
            'roomid' => $this->cid,
            'message' => 'Test',
            'dup' => 1
        ]);
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

    public function testImage() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        $ret = $this->call('chatrooms', 'PUT', [
            'userid' => $this->uid2
        ]);

        assertEquals(0, $ret['ret']);
        $this->cid = $ret['id'];
        assertNotNull($this->cid);

        # Create a chat to the second user with a referenced image
        $data = file_get_contents(IZNIK_BASE . '/test/ut/php/images/chair.jpg');
        file_put_contents("/tmp/pan.jpg", $data);

        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/pan.jpg',
                'type' => 'image/jpeg'
            ],
            'chatmessage' => 1,
            'imgtype' => 'ChatMessage'
        ]);

        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $iid = $ret['id'];

        $ret = $this->call('chatmessages', 'POST', [
            'roomid' => $this->cid,
            'imageid' => $iid
        ]);
        error_log("Create image " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid1 = $ret['id'];

        # Now log in as the other user.
        assertTrue($this->user2->login('testpw'));

        # Should see the messages
        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid,
            'id' => $mid1
        ]);
        error_log("Get message " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals($mid1, $ret['chatmessage']['id']);
        assertEquals($iid, $ret['chatmessage']['image']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testLink() {
        error_log(__METHOD__);

        $m = new ChatMessage($this->dbhr, $this->dbhm);;

        assertTrue($m->checkReview("Hi ↵↵repetitionbowie.com/sportscapping.php?meant=mus2x216xkrn0mpb↵↵↵↵↵Thank you!"));

        error_log(__METHOD__ . " end");
    }

    public function testReview() {
        error_log(__METHOD__);

        $this->dbhm->preExec("DELETE FROM spam_whitelist_links WHERE domain LIKE 'spam.wherever';");
        assertTrue($this->user->login('testpw'));

        # Create a chat to the second user
        $ret = $this->call('chatrooms', 'PUT', [
            'userid' => $this->uid2
        ]);

        assertEquals(0, $ret['ret']);
        $this->cid = $ret['id'];
        assertNotNull($this->cid);

        $ret = $this->call('chatmessages', 'POST', [
            'roomid' => $this->cid,
            'message' => 'Test with link http://spam.wherever ',
            'refchatid' => $this->cid
        ]);
        error_log("Create message " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid1 = $ret['id'];

        $ret = $this->call('chatmessages', 'POST', [
            'roomid' => $this->cid,
            'message' => 'Test with link http://ham.wherever '
        ]);
        error_log("Create message " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $mid2 = $ret['id'];

        # Now log in as the other user.
        assertTrue($this->user2->login('testpw'));

        # Shouldn't see chat as no messages not held for review.
        $ret = $this->call('chatrooms', 'GET', []);
        error_log("Shouldn't see rooms " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        self::assertEquals(0, count($ret['chatrooms']));

        # Shouldn't see messages as held for review.
        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid
        ]);
        error_log("Get message" . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['chatmessages']));

        # Now log in as a third user.
        assertTrue($this->user3->login('testpw'));
        $this->user3->addMembership($this->groupid, User::ROLE_MODERATOR);

        $this->user2->removeMembership($this->groupid);

        # We're a mod, but not on any of the groups that these users are on (because they're not on any).  So we
        # shouldn't see this chat message to review.
        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, $ret['work']['chatreview']);

        $this->user->addMembership($this->groupid, User::ROLE_MODERATOR);

        # Shouldn't see this as the recipient is still not on a group we mod.
        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, $ret['work']['chatreview']);

        $this->user2->addMembership($this->groupid, User::ROLE_MODERATOR);

        # Should see this now.
        error_log("Check can see for mod on {$this->groupid}");
        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(2, $ret['work']['chatreview']);

        # Test the 'other' variant.
        $this->user2->setGroupSettings($this->groupid, [ 'active' => 0 ]);
        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, $ret['work']['chatreviewother']);

        # Get the messages for review.
        $ret = $this->call('chatmessages', 'GET', []);
        assertEquals(0, $ret['ret']);
        error_log("Messages for review " . var_export($ret, TRUE));
        assertEquals(2, count($ret['chatmessages']));
        assertEquals($mid1, $ret['chatmessages'][0]['id']);
        assertEquals(ChatMessage::TYPE_REPORTEDUSER, $ret['chatmessages'][0]['type']);
        assertEquals($mid2, $ret['chatmessages'][1]['id']);

        # Approve the first
        $ret = $this->call('chatmessages', 'POST', [
            'action' => 'Approve',
            'id' => $mid1
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('chatmessages', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatmessages']));
        assertEquals($mid2, $ret['chatmessages'][0]['id']);

        # Reject the second
        $ret = $this->call('chatmessages', 'POST', [
            'action' => 'Reject',
            'id' => $mid2
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('chatmessages', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['chatmessages']));

        # Now log in as the recipient.  Should see the approved one.
        assertTrue($this->user2->login('testpw'));

        $ret = $this->call('chatmessages', 'GET', [
            'roomid' => $this->cid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['chatmessages']));
        assertEquals($mid1, $ret['chatmessages'][0]['id']);

        error_log(__METHOD__ . " end");
    }
}
