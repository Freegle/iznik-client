<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/misc/Location.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class messageAPITest extends IznikAPITestCase
{
    public $dbhr, $dbhm;

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testApproved()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);
        $a->setYahooApprovedId($group1, 42);

        # Should be able to see this message even logged out.
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Approved'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);
        assertFalse(array_key_exists('fromuser', $ret['message']));

        # We should be able to see it using the legacy id.
        $ret = $this->call('message', 'GET', [
            'id' => "L42",
            'groupid' => $group1
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        # When logged in should be able to see message history.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Approved'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);
        assertFalse(array_key_exists('emails', $ret['message']['fromuser']));

        $atts = $a->getPublic();
        assertEquals(1, count($atts['fromuser']['messagehistory']));
        assertEquals($id, $atts['fromuser']['messagehistory'][0]['id']);
        assertEquals('Other', $atts['fromuser']['messagehistory'][0]['type']);
        assertEquals('Basic test', $atts['fromuser']['messagehistory'][0]['subject']);

        $a->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testBadColl()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        # Shouldn't be able to see a bad collection
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'BadColl'
        ]);
        assertEquals(101, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testPending()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);

        # Shouldn't be able to see pending logged out
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Pending'
        ]);
        assertEquals(1, $ret['ret']);

        # Now join - shouldn't be able to see a pending message as user
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Pending'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to mod - should be able to see it.
        $u->setRole(User::ROLE_MODERATOR, $group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Pending'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        $a->delete();

        error_log(__METHOD__ . " end");
    }

    public function testSpam()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/spam');
        $msg = str_ireplace('To: FreeglePlayground <freegleplayground@yahoogroups.com>', 'To: "testgroup@yahoogroups.com" <testgroup@yahoogroups.com>', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from1@test.com', 'to@test.com', $msg);
        error_log("Created spam message $id");
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals($id, $a->getID());
        assertTrue(array_key_exists('subject', $a->getPublic()));

        # Shouldn't be able to see spam logged out
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        assertEquals(1, $ret['ret']);

        # Now join - shouldn't be able to see a spam message as user
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to see it.
        $u->setRole(User::ROLE_OWNER, $group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        # Delete it - as a user should fail
        $u->setRole(User::ROLE_MEMBER, $group1);
        $ret = $this->call('message', 'DELETE', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Approved'
        ]);
        assertEquals(2, $ret['ret']);

        $u->setRole(User::ROLE_OWNER, $group1);
        $ret = $this->call('message', 'DELETE', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);

        # Try again to see it - should be gone
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        assertEquals(3, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testSpamToApproved()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/spam');
        $msg = str_ireplace('To: FreeglePlayground <freegleplayground@yahoogroups.com>', 'To: "testgroup@yahoogroups.com" <testgroup@yahoogroups.com>', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from1@test.com', 'to@test.com', $msg);
        error_log("Created spam message $id");
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals($id, $a->getID());
        assertTrue(array_key_exists('subject', $a->getPublic()));

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1, User::ROLE_OWNER);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        # Mark as not spam.
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'NotSpam'
        ]);
        assertEquals(0, $ret['ret']);

        # Try again to see it - should be gone from spam into approved
        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        error_log("Should be in approved " . var_export($ret['message']['groups'], TRUE));
        assertEquals('Approved', $ret['message']['groups'][0]['collection']);

        # Now send it again - should stay in approved.
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id2 = $r->received(Message::YAHOO_APPROVED, 'from1@test.com', 'to@test.com', $msg);
        error_log("Created spam message $id");
        $rc = $r->route();
        #assertEquals(MailRouter::INCOMING_SPAM, $rc);
        self::assertEquals($id, $id2);

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        error_log("Should still be in approved " . var_export($ret['message']['groups'], TRUE));
        assertEquals('Approved', $ret['message']['groups'][0]['collection']);

        error_log(__METHOD__ . " end");
    }

    public function testApprove()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);

        # Send from a user at our domain, so that we can cover the reply going back to them
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create('Test', 'User', 'Test User');
        $email = 'ut-' . rand() . '@' . USER_DOMAIN;
        $u->addEmail('from@test.com');
        $u->addEmail($email);

        $id = $r->received(Message::YAHOO_PENDING, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        $this->dbhm->preExec("UPDATE messages_groups SET yahooapprove = 'test@test.com', yahoopendingid = 1 WHERE msgid = $id;");

        # Suppress mails.
        $m = $this->getMockBuilder('Message')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('mailer'))
            ->getMock();
        $m->method('mailer')->willReturn(false);

        # Shouldn't be able to approve logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Approve'
        ]);
        assertEquals(2, $ret['ret']);

        $ret = $this->call('plugin', 'GET', []);
        assertEquals(1, $ret['ret']);

        # Now join - shouldn't be able to approve as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Approve',
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to approve it.  Suppress the mail.
        $u->setRole(User::ROLE_OWNER, $group1);

        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('Test');
        $c->setPrivate('ccrejectto', 'Specific');
        $c->setPrivate('ccrejectaddr', 'test@test.com');

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s = new StdMessage($this->dbhr, $this->dbhm, $sid);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Approve',
            'duplicate' => 1,
            'subject' => 'Test',
            'body' => 'Test',
            'stdmsgid' => $sid
        ]);
        assertEquals(0, $ret['ret']);

        # Sleep for background logging
        $this->waitBackground();

        # Get the logs - should reference the stdmsg.
        $ret = $this->call('user', 'GET', [
            'id' => $uid,
            'logs' => TRUE
        ]);

        $log = $this->findLog('Message', 'Approved', $ret['user']['logs']);
        assertEquals($sid, $log['stdmsgid']);

        $s->delete();
        $c->delete();

        # Message should now exist but approved.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        $groups = $m->getGroups();
        assertEquals($group1, $groups[0]);
        $p = $m->getPublic();
        error_log("After approval " . var_export($p, TRUE));
        assertEquals('Approved', $p['groups'][0]['collection']);
        assertEquals($uid, $p['groups'][0]['approvedby']['id']);

        # Plugin work should exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['plugin']));
        assertEquals($group1, $ret['plugin'][0]['groupid']);
        assertEquals('{"type":"ApprovePendingMessage","id":"1"}', $ret['plugin'][0]['data']);
        $pid = $ret['plugin'][0]['id'];

        $ret = $this->call('plugin', 'DELETE', [
            'id' => $pid
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, count($ret['plugin']));

        # Should be gone
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Approve',
            'duplicate' => 2
        ]);
        assertEquals(3, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testReject()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_OTHER);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Subject: Basic test', 'Subject: OFFER: thing (place)', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        $this->dbhm->preExec("UPDATE messages_groups SET yahooreject = 'test@test.com', yahoopendingid = 1 WHERE msgid = $id;");

        # Suppress mails.
        $m = $this->getMockBuilder('Message')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('mailer'))
            ->getMock();
        $m->method('mailer')->willReturn(false);

        assertEquals(Message::TYPE_OTHER, $m->getType());

        # Shouldn't be able to reject logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reject'
        ]);
        assertEquals(2, $ret['ret']);

        # Now join - shouldn't be able to reject as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reject',
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to reject it.  Suppress the mail.
        $u->setRole(User::ROLE_OWNER, $group1);

        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('Test');
        $c->setPrivate('ccrejectto', 'Me');
        $c->setPrivate('fromname', 'Groupname Moderator');

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s = new StdMessage($this->dbhr, $this->dbhm, $sid);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'stdmsgid' => $sid,
            'action' => 'Reject',
            'subject' => 'Test reject',
            'body' => 'Test body',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $s->delete();
        $c->delete();

        # Plugin work should exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['plugin']));
        assertEquals($group1, $ret['plugin'][0]['groupid']);
        assertEquals('{"type":"RejectPendingMessage","id":"1"}', $ret['plugin'][0]['data']);
        $pid = $ret['plugin'][0]['id'];

        # The message should exist as rejected.  Log in as the sender.
        $uid = $m->getFromuser();
        error_log("Found sender as $uid");
        $u = User::get($this->dbhm, $this->dbhm, $uid);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        error_log("Message $id should now be rejected");
        $ret = $this->call('messages', 'GET', [
            'collection' => MessageCollection::REJECTED
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['messages']));
        assertEquals($id, $ret['messages'][0]['id']);

        # Try to convert it back to a draft.
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'RejectToDraft'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('messages', 'GET', [
            'collection' => MessageCollection::DRAFT
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['messages']));
        assertEquals($id, $ret['messages'][0]['id']);

        # Coverage of rollback case.
        $m2 = new Message($this->dbhr, $this->dbhm);
        assertFalse($m2->backToDraft());

        # Now delete it.
        $ret = $this->call('plugin', 'DELETE', [
            'id' => $pid
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, count($ret['plugin']));

        # Should be gone
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reject',
            'duplicate' => 2
        ]);
        assertEquals(3, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testReply()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_OTHER);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        # Suppress mails.
        $m = $this->getMockBuilder('Message')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('mailer'))
            ->getMock();
        $m->method('mailer')->willReturn(false);

        assertEquals(Message::TYPE_OTHER, $m->getType());

        # Shouldn't be able to mail logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reply'
        ]);
        assertEquals(2, $ret['ret']);

        # Now join - shouldn't be able to mail as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reply',
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to reply.  Suppress the mail.
        $u->setRole(User::ROLE_OWNER, $group1);

        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('Test');
        $c->setPrivate('ccrejectto', 'Specifc');
        $c->setPrivate('ccrejectaddr', 'test@test.com');

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s->setPrivate('action', 'Leave Approved Message');
        $s = new StdMessage($this->dbhr, $this->dbhm, $sid);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reply',
            'stdmsgid' => $sid,
            'subject' => 'Test reply',
            'body' => 'Test body',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $s->delete();
        $c->delete();

        # Plugin work shouldn't exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['plugin']));

        error_log(__METHOD__ . " end");
    }

    public function testDelete()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        $this->dbhm->preExec("UPDATE messages_groups SET yahooreject = 'test@test.com', yahoopendingid = 1, yahooapprovedid = NULL WHERE msgid = $id;");

        # Shouldn't be able to delete logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Delete'
        ]);
        assertEquals(2, $ret['ret']);

        # Now join - shouldn't be able to delete as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Delete',
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to delete it.
        $u->setRole(User::ROLE_OWNER, $group1);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Delete',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        # Plugin work should exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
        error_log("Plugin work after delete " . var_export($ret['plugin'], TRUE));
        assertEquals(1, count($ret['plugin']));
        assertEquals($group1, $ret['plugin'][0]['groupid']);
        assertEquals('{"type":"RejectPendingMessage","id":"1"}', $ret['plugin'][0]['data']);
        $pid = $ret['plugin'][0]['id'];

        $ret = $this->call('plugin', 'DELETE', [
            'id' => $pid
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, count($ret['plugin']));

        # Should be gone
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reject',
            'duplicate' => 2
        ]);
        assertEquals(3, $ret['ret']);

        # Route and delete approved.
        error_log("Route and delete approved");
        $msg = $this->unique($msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Delete'
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testNotSpam()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $origmsg = file_get_contents('msgs/spam');
        $msg = str_ireplace('To: FreeglePlayground <freegleplayground@yahoogroups.com>', 'To: "testgroup@yahoogroups.com" <testgroup@yahoogroups.com>', $origmsg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from1@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        # Shouldn't be able to do this logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'NotSpam'
        ]);
        assertEquals(2, $ret['ret']);

        $ret = $this->call('plugin', 'GET', []);
        assertEquals(1, $ret['ret']);

        # Now join - shouldn't be able to do this as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'NotSpam',
            'dup' => 2
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to do this it.
        $u->setRole(User::ROLE_OWNER, $group1);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'NotSpam',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        # Message should now be in pending.
        $ret = $this->call('messages', 'GET', [
            'groupid' => $group1,
            'collection' => 'Pending'
        ]);
        $msgs = $ret['messages'];
        assertEquals(1, count($msgs));
        assertEquals($id, $msgs[0]['id']);

        # Spam should be empty.
        $ret = $this->call('messages', 'GET', [
            'groupid' => $group1,
            'collection' => 'Spam'
        ]);
        $msgs = $ret['messages'];
        assertEquals(0, count($msgs));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);

        # Pending should be empty.
        $ret = $this->call('messages', 'GET', [
            'groupid' => $group1,
            'collection' => 'Pending'
        ]);
        $msgs = $ret['messages'];
        assertEquals(0, count($msgs));

        # Again as admin
        $u->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);

        $msg = str_ireplace('To: FreeglePlayground <freegleplayground@yahoogroups.com>', 'To: "testgroup@yahoogroups.com" <testgroup@yahoogroups.com>', $origmsg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from1@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);

        # Pending should be empty.
        $ret = $this->call('messages', 'GET', [
            'groupid' => $group1,
            'collection' => 'Pending'
        ]);
        $msgs = $ret['messages'];
        assertEquals(0, count($msgs));

        error_log(__METHOD__ . " end");
    }

    public function testHold()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        $this->dbhm->preExec("UPDATE messages_groups SET yahooreject = 'test@test.com', yahoopendingid = 1 WHERE msgid = $id;");

        # Shouldn't be able to hold logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Hold'
        ]);
        assertEquals(2, $ret['ret']);

        # Now join - shouldn't be able to hold as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Hold',
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to hold it.
        $u->setRole(User::ROLE_OWNER, $group1);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Hold',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1
        ]);
        assertEquals($uid, $ret['message']['heldby']['id']);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Release',
            'duplicate' => 2
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'groupid' => $group1
        ]);
        assertFalse(pres('heldby', $ret['message']));

        error_log(__METHOD__ . " end");
    }

    public function testEdit()
    {
        error_log(__METHOD__);

        # Create an attachment
        $cwd = getcwd();
        error_log("Initial $cwd");
        $data = file_get_contents('images/chair.jpg');
        $msg = $this->unique(file_get_contents('msgs/basic'));
        file_put_contents("/tmp/chair.jpg", $data);
        chdir($cwd);
        error_log("After " . getcwd());

        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/chair.jpg',
                'type' => 'image/jpeg'
            ],
            'identify' => FALSE
        ]);

        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $attid = $ret['id'];

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_OTHER);

        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        # Shouldn't be able to edit logged out
        $ret = $this->call('message', 'PATCH', [
            'id' => $id,
            'groupid' => $group1,
            'subject' => 'Test edit',
            'attachments' => []
        ]);

        error_log(var_export($ret, true));
        assertEquals(2, $ret['ret']);

        # Now join - shouldn't be able to edit as a member
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'PATCH', [
            'id' => $id,
            'groupid' => $group1,
            'subject' => 'Test edit'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to edit it.
        $u->setRole(User::ROLE_OWNER, $group1);

        $ret = $this->call('message', 'PATCH', [
            'id' => $id,
            'groupid' => $group1,
            'subject' => 'Test edit'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        assertEquals('Test edit', $ret['message']['subject']);
        assertEquals('Test edit', $ret['message']['suggestedsubject']);

        $ret = $this->call('message', 'PATCH', [
            'id' => $id,
            'groupid' => $group1,
            'textbody' => 'Test edit',
            'attachments' => [ $attid ]
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        assertEquals('Test edit', $ret['message']['textbody']);
        error_log("After text edit " . var_export($ret, TRUE));

        $ret = $this->call('message', 'PATCH', [
            'id' => $id,
            'groupid' => $group1,
            'htmlbody' => 'Test edit',
            'FOP' => 0
        ]);
        assertEquals(0, $ret['ret']);
        error_log("After HTML edit " . var_export($ret, TRUE));

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        assertEquals('Test edit', $ret['message']['htmlbody']);
        self::assertEquals(0, $ret['message']['FOP']);

        error_log(__METHOD__ . " end");
    }

    public function testDraft()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Can create drafts when not logged in.
        $data = file_get_contents('images/chair.jpg');
        file_put_contents("/tmp/chair.jpg", $data);

        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/chair.jpg',
                'type' => 'image/jpeg'
            ],
            'identify' => TRUE
        ]);

        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $attid = $ret['id'];

        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'textbody' => 'Text body',
            'locationid' => 1,
            'attachments' => [ $attid ]
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        # And again to exercise codepath
        $ret = $this->call('message', 'PUT', [
            'id' => $id,
            'collection' => 'Draft',
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'textbody' => 'Text body',
            'locationid' => 1,
            'groupid' => $group1,
            'attachments' => [ $attid ]
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        $msg = $ret['message'];
        assertEquals('Offer', $msg['type']);
        assertEquals('a thing', $msg['subject']);
        assertEquals('Text body', $msg['textbody']);
        assertEquals($attid, $msg['attachments'][0]['id']);

        # Now create a new attachment and update the draft.
        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/chair.jpg',
                'type' => 'image/jpeg',
                'dedup' => 1
            ],
            'identify' => TRUE
        ]);

        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $attid2 = $ret['id'];

        $ret = $this->call('message', 'PUT', [
            'id' => $id,
            'collection' => 'Draft',
            'messagetype' => 'Wanted',
            'item' => 'a thing2',
            'locationid' => 1,
            'textbody' => 'Text body2',
            'attachments' => [ $attid2 ]
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['id']);

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        $msg = $ret['message'];
        assertEquals('Wanted', $msg['type']);
        assertEquals('a thing2', $msg['subject']);
        assertEquals('Text body2', $msg['textbody']);
        assertEquals($attid2, $msg['attachments'][0]['id']);

        $ret = $this->call('messages', 'GET', [
            'collection' => 'Draft'
        ]);
        error_log("Messages " . var_export($ret, TRUE));
        assertEquals($id, $ret['messages'][0]['id']);

        # Now remove the attachment
        $ret = $this->call('message', 'PUT', [
            'id' => $id,
            'collection' => 'Draft',
            'messagetype' => 'Wanted',
            'locationid' => 1,
            'item' => 'a thing2',
            'textbody' => 'Text body2'
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['id']);

        $ret = $this->call('messages', 'GET', [
            'collection' => 'Draft'
        ]);
        error_log("Messages " . var_export($ret, TRUE));
        assertEquals($id, $ret['messages'][0]['id']);
        assertEquals(0, count($ret['messages'][0]['attachments']));

        error_log(__METHOD__ . " end");
    }

    public function testSubmit()
    {
        error_log(__METHOD__);

        # Set a fake IP for coverage reasons; choose the BBC.  No license fee required.
        $_SERVER['REMOTE_ADDR'] = '212.58.244.22';

        $email = 'test-' . rand() . '@blackhole.io';

        # This is similar to the actions on the client
        # - find a location close to a lat/lng
        # - upload a picture
        # - create a draft with a location
        # - find the closest group to that location
        # - submit it
        $this->group = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_REUSE);
        $this->group->setPrivate('onyahoo', 1);
        $this->group->setPrivate('lat', 8.5);
        $this->group->setPrivate('lng', 179.3);
        $this->group->setPrivate('poly', 'POLYGON((179.1 8.3, 179.2 8.3, 179.2 8.4, 179.1 8.4, 179.1 8.3))');
        $this->group->setPrivate('publish', 1);

        $l = new Location($this->dbhr, $this->dbhm);
        $locid = $l->create(NULL, 'Tuvalu Postcode', 'Postcode', 'POINT(179.2167 8.53333)',0);

        $data = file_get_contents('images/chair.jpg');
        file_put_contents("/tmp/chair.jpg", $data);

        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/chair.jpg',
                'type' => 'image/jpeg'
            ],
            'identify' => TRUE
        ]);

        error_log("Create attachment " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $attid = $ret['id'];

        # Submit to the playground group explicitly.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'locationid' => $locid,
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'groupid' => $gid,
            'textbody' => 'Text body',
            'attachments' => [ $attid ]
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created draft $id");

        # This will get sent; will get queued, as we don't have a membership for the group
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'JoinAndPost',
            'email' => $email,
            'ignoregroupoverride' => true
        ]);

        error_log("Message #$id should be queued " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals('Queued for group membership', $ret['status']);
        $applied = $ret['appliedemail'];

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->findByEmail($email);

        # This assumes the playground group is set to auto-approve and moderate new messages.
        #
        # Now when that approval gets notified to us, it should trigger submission of the
        # messages from that user.
        $count = 0;
        $found = FALSE;

        do {
            error_log("...waiting for pending message from $applied #$uid, try $count");
            sleep(1);
            $msgs = $this->dbhr->preQuery("SELECT * FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND groupid = ? AND messages_groups.collection = ? AND fromuser = ?;",
                [ $gid, MessageCollection::PENDING, $uid ]);
            foreach ($msgs as $msg) {
                error_log("Reached pending " . var_export($msg, TRUE));
                $found = TRUE;
            }
            $count++;
        } while ($count < IznikTestCase::YAHOO_PATIENCE && !$found);

        assertTrue($found, "Yahoo slow?  Failed to reach pending messages");

        $m = new Message($this->dbhr, $this->dbhm, $id);
        $m->delete("UT delete");

        # And again, now that the user exists.  Set an invalid from IP which will
        # fail to resolve.
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';

        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'locationid' => $locid,
            'groupid' => $this->groupid,
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'textbody' => 'Text body',
            'attachments' => [ $attid ]
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created draft $id");

        # This will get queued, as we don't have a membership for the group
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'JoinAndPost',
            'email' => $email,
            'ignoregroupoverride' => true
        ]);

        error_log("Message #$id should be queued 2 " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals('Queued for group membership', $ret['status']);

        $count = 0;
        $found = FALSE;

        do {
            error_log("...waiting for pending message from $applied #$uid, try $count");
            sleep(1);
            $msgs = $this->dbhr->preQuery("SELECT * FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND groupid = ? AND messages_groups.collection = ? AND fromuser = ?;",
                [ $gid, MessageCollection::PENDING, $uid ]);
            foreach ($msgs as $msg) {
                error_log("Reached pending " . var_export($msg, TRUE));
                $found = TRUE;
                $m = new Message($this->dbhr, $this->dbhm, $msg['msgid']);
                $m->delete('UT');
            }
            $count++;
        } while ($count < IznikTestCase::YAHOO_PATIENCE && !$found);

        assertTrue($found, "Yahoo slow?  Failed to reach pending messages");

        $m = new Message($this->dbhr, $this->dbhm, $id);
        $m->delete("UT delete");

        # And once again, now that the user exists and will be a member of the group.
        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'locationid' => $locid,
            'groupid' => $gid,
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'textbody' => 'Text body',
            'attachments' => [ $attid ]
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created draft $id");

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'JoinAndPost',
            'email' => $email,
            'ignoregroupoverride' => true
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals('Success', $ret['status']);

        $count = 0;
        $found = FALSE;

        do {
            error_log("...waiting for pending message from $applied #$uid, try $count");
            sleep(1);
            $msgs = $this->dbhr->preQuery("SELECT * FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND groupid = ? AND messages_groups.collection = ? AND fromuser = ?;",
                [ $gid, MessageCollection::PENDING, $uid ]);
            foreach ($msgs as $msg) {
                error_log("Reached pending " . var_export($msg, TRUE));
                $found = TRUE;
                $m = new Message($this->dbhr, $this->dbhm, $msg['msgid']);
                $m->delete('UT');
            }
            $count++;
        } while ($count < IznikTestCase::YAHOO_PATIENCE && !$found);

        assertTrue($found, "Yahoo slow?  Failed to reach pending messages");

        # Now approve the message and wait for it to reach the group.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        $m->approve($gid, NULL, NULL, NULL);

        do {
            error_log("...waiting for approved message from $applied #$uid, try $count");
            sleep(1);
            $msgs = $this->dbhr->preQuery("SELECT * FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id AND groupid = ? AND messages_groups.collection = ? AND fromuser = ? AND yahooapprovedid IS NOT NULL;",
                [ $gid, MessageCollection::APPROVED, $uid ]);
            foreach ($msgs as $msg) {
                error_log("Reached approved" . var_export($msg, TRUE));
                $found = TRUE;
                $m = new Message($this->dbhr, $this->dbhm, $msg['msgid']);

                # Check that the attachment is present.
                $atts = $m->getAttachments();
                assertEquals(1, count($atts));

                $m->delete('UT');
            }
            $count++;
        } while ($count < IznikTestCase::YAHOO_PATIENCE && !$found);

        assertTrue($found, "Yahoo slow?  Failed to reach pending messages");

        error_log(__METHOD__ . " end");
    }

    public function testSubmit2()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        # This is similar to the actions on the client
        # - find a location close to a lat/lng
        # - upload a picture
        # - create a draft with a location
        # - find the closest group to that location
        # - submit it
        $this->group = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_REUSE);
        $this->group->setPrivate('lat', 8.5);
        $this->group->setPrivate('lng', 179.3);
        $this->group->setPrivate('poly', 'POLYGON((179.1 8.3, 179.2 8.3, 179.2 8.4, 179.1 8.4, 179.1 8.3))');

        $l = new Location($this->dbhr, $this->dbhm);
        $locid = $l->create(NULL, 'Tuvalu Postcode', 'Postcode', 'POINT(179.2167 8.53333)',0);

        $data = file_get_contents('images/chair.jpg');
        file_put_contents("/tmp/chair.jpg", $data);

        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/chair.jpg',
                'type' => 'image/jpeg'
            ],
            'identify' => TRUE
        ]);

        error_log("Create attachment " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $attid = $ret['id'];

        # Find a location
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'locationid' => $locid,
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'groupid' => $gid,
            'textbody' => 'Text body',
            'attachments' => [ $attid ]
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created draft $id");

        # This will get sent; will get queued, as we don't have a membership for the group
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'JoinAndPost',
            'email' => $email,
            'ignoregroupoverride' => true
        ]);

        error_log("Message #$id should be queued " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals('Queued for group membership', $ret['status']);
        $applied = $ret['appliedemail'];

        # Now to get coverage, invoke the submission arm in here, rather than on the separate mail server.  This
        # assumes we run tests faster than Yahoo responds.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->findByEmail($email);
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        error_log("User id $uid");
//        $eid = $u->addEmail($applied);
//        error_log("Added email $eid");
        $emails = $u->getEmails();
        error_log("Email " . var_export($emails, TRUE));
        $gemail = NULL;
        foreach ($emails as $anemail) {
            if ($anemail['email'] != $email) {
                $gemail = $anemail['id'];
            }
        }
        $u->addMembership($gid, User::ROLE_MEMBER, $gemail);

        $rc = $u->submitYahooQueued($gid);
        assertEquals(1, $rc);

        error_log(__METHOD__ . " end");
    }

    public function testSubmitNative()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        # This is similar to the actions on the client
        # - find a location close to a lat/lng
        # - upload a picture
        # - create a draft with a location
        # - find the closest group to that location
        # - submit it
        $this->group = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_FREEGLE);

        $this->group->setPrivate('onyahoo', 0);

        error_log("Set private for {$this->groupid} to " . $this->group->getPrivate('onyahoo'));

        $this->group->setPrivate('lat', 8.5);
        $this->group->setPrivate('lng', 179.3);
        $this->group->setPrivate('poly', 'POLYGON((179.1 8.3, 179.2 8.3, 179.2 8.4, 179.1 8.4, 179.1 8.3))');
        $this->group->setPrivate('publish', 1);

        $l = new Location($this->dbhr, $this->dbhm);
        $locid = $l->create(NULL, 'Tuvalu Postcode', 'Postcode', 'POINT(179.2167 8.53333)',0);

        # Find a location
        $g = Group::get($this->dbhr, $this->dbhm);

        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'locationid' => $locid,
            'messagetype' => 'Offer',
            'item' => 'a thing',
            'groupid' => $this->groupid,
            'textbody' => 'Text body'
        ]);

        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created draft $id");

        # This will get sent as for native groups we can do so immediate.
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'JoinAndPost',
            'email' => $email,
            'ignoregroupoverride' => true
        ]);

        error_log("Message #$id should be pending " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals('Success', $ret['status']);

        $c = new MessageCollection($this->dbhr, $this->dbhm, MessageCollection::PENDING);
        $ctx = NULL;
        list ($groups, $msgs) = $c->get($ctx, 10, [ $this->groupid ]);
        error_log("Got pending messages " . var_export($msgs, TRUE));
        assertEquals(1, count($msgs));
        self::assertEquals($id, $msgs[0]['id']);

        # Now approve the message and wait for it to reach the group.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        $m->approve($this->groupid, NULL, NULL, NULL);

        $c = new MessageCollection($this->dbhr, $this->dbhm, MessageCollection::PENDING);
        $ctx = NULL;
        list ($groups, $msgs) = $c->get($ctx, 10, [ $this->groupid ]);
        assertEquals(0, count($msgs));

        $c = new MessageCollection($this->dbhr, $this->dbhm, MessageCollection::APPROVED);
        $ctx = NULL;
        list ($groups, $msgs) = $c->get($ctx, 10, [ $this->groupid ]);
        assertEquals(1, count($msgs));
        self::assertEquals($id, $msgs[0]['id']);

        error_log(__METHOD__ . " end");
    }

    public function testDoubleModeration() {
        error_log(__METHOD__);

        # We set up a pending message, then approve it, then get a notification from Yahoo that it's pending.
        #
        # This should result in the message remaining approved, and us trying to approve it on Yahoo.
        $email = 'test-' . rand() . '@blackhole.io';

        # This is similar to the actions on the client
        # - find a location close to a lat/lng
        # - upload a picture
        # - create a draft with a location
        # - find the closest group to that location
        # - submit it
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $locationid = $this->dbhr->preQuery("SELECT id FROM locations WHERE type = 'Postcode' AND LOCATE(' ', name) > 0 LIMIT 1;")[0]['id'];
        error_log("Use location $locationid");

        $ret = $this->call('message', 'PUT', [
            'collection' => 'Draft',
            'locationid' => $locationid,
            'messagetype' => 'Offer',
            'item' => 'a double moderation test',
            'groupid' => $gid,
            'textbody' => 'Text body'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        error_log("Created draft $id");

        # This will get sent; will get queued, as we don't have a membership for the group
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'JoinAndPost',
            'email' => $email,
            'ignoregroupoverride' => true
        ]);

        error_log("Message #$id should be queued " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals('Queued for group membership', $ret['status']);

        # Now we will apply for a membership, get it, and then call submitYahooQueued.  At that point the message
        # will become pending.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        error_log("Wait for submit");
        $count = 0;
        do {
            $stop = FALSE;
            $groups = $m->getGroups(FALSE, FALSE);
            error_log(var_export($groups, TRUE));
            error_log("Check $count pending...");
            if (MessageCollection::PENDING == $groups[0]['collection']) {
                $stop = TRUE;
            } else {
                sleep(1);
            }
            $count++;
        } while (!$stop && $count < IznikTestCase::YAHOO_PATIENCE);

        assertLessThan(IznikTestCase::YAHOO_PATIENCE, $count);

        # Now it's pending - approve it on the platform, before Yahoo has seen it.
        error_log("Approve");
        $m->approve($gid, NULL, NULL, NULL);
        
        # We will then get notified of the message being pending on Yahoo, which will trigger an approval, and then
        # we will get the approved message back. At that point the message will acquire a yahooapprovedid - so that's
        # what we wait for to show this whole process works.
        error_log("Wait for Yahoo approved");
        $count = 0;
        do {
            $stop = FALSE;
            $groups = $m->getGroups(FALSE, FALSE);
            error_log(var_export($groups, TRUE));
            error_log("Check $count approved id {$groups[0]['yahooapprovedid']}...");
            assertEquals(MessageCollection::APPROVED, $groups[0]['collection']);
            if ($groups[0]['yahooapprovedid']) {
                $stop = TRUE;
            } else {
                sleep(1);
            }
            $count++;
        } while (!$stop && $count < IznikTestCase::YAHOO_PATIENCE);

        assertLessThan(IznikTestCase::YAHOO_PATIENCE, $count, "Yahoo slow?");

        error_log(__METHOD__ . " end");
    }

    public function testCrosspost() {
        error_log(__METHOD__);

        # At the moment a crosspost results in two separate messages - see comment in Message::save().
        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id1 = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        $msg = str_ireplace('testgroup1', 'testgroup2', $msg);
        $id2 = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        $m1 = new Message($this->dbhr, $this->dbhm, $id1);
        $m2 = new Message($this->dbhr, $this->dbhm, $id2);
        assertNotEquals($m1->getMessageID(), $m2->getMessageID());
        $m1->delete("UT delete");
        $m2->delete("UT delete");

        error_log(__METHOD__ . " end");
    }
    
    public function testPromise() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_FREEGLE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid1 = $u->create(NULL, NULL, 'Test User');
        $u->addEmail('test@test.com');
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        
        $uid2 = $u->create(NULL, NULL, 'Test User');
        $uid3 = $u->create(NULL, NULL, 'Test User');

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_ireplace('Basic test', 'OFFER: A thing (A place)', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'test@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Shouldn't be able to promise logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'userid' => $uid1,
            'action' => 'Promise'
        ]);
        assertEquals(2, $ret['ret']);

        # Promise it to the other user.
        $u = User::get($this->dbhr, $this->dbhm, $uid1);
        assertTrue($u->login('testpw'));
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'userid' => $uid2,
            'action' => 'Promise'
        ]);
        assertEquals(0, $ret['ret']);
        
        # Promise should show
        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        error_log("Got message " . var_export($ret, TRUE));
        assertEquals(1, count($ret['message']['promises']));
        assertEquals($uid2, $ret['message']['promises'][0]['userid']);

        # Can promise to multiple users
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'userid' => $uid3,
            'action' => 'Promise'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(2, count($ret['message']['promises']));
        assertEquals($uid3, $ret['message']['promises'][0]['userid']);
        
        # Renege on one of them.
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'userid' => $uid2,
            'action' => 'Renege'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['message']['promises']));
        assertEquals($uid3, $ret['message']['promises'][0]['userid']);
        
        # Check we can't promise on someone else's message.
        $u = User::get($this->dbhr, $this->dbhm, $uid3);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'userid' => $uid3,
            'action' => 'Promise'
        ]);
        assertEquals(2, $ret['ret']);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'userid' => $uid3,
            'action' => 'Renege'
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testMark()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail($email);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $origmsg = file_get_contents('msgs/basic');
        $msg = $this->unique($origmsg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Basic test', 'OFFER: a thing (A Place)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'OutcomeIntended',
            'outcome' => Message::OUTCOME_TAKEN
        ]);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'Outcome',
            'outcome' => Message::OUTCOME_TAKEN,
            'happiness' => User::FINE,
            'userid' => $uid
        ]);
        assertEquals(0, $ret['ret']);

        $msg = $this->unique($origmsg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Basic test', 'WANTED: a thing (A Place)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals(Message::TYPE_WANTED, $m->getType());

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'Outcome',
            'outcome' => Message::OUTCOME_RECEIVED,
            'happiness' => User::FINE,
            'userid' => $uid
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'Outcome',
            'outcome' => Message::OUTCOME_WITHDRAWN,
            'happiness' => User::FINE,
            'comment' => "It was fine",
            'userid' => $uid
        ]);
        assertEquals(0, $ret['ret']);

        # Now get the happiness back.
        $u->setRole(User::ROLE_MODERATOR, $group1);
        assertTrue($u->login('testpw'));
        $ret = $this->call('memberships', 'GET', [
            'collection' => 'Happiness',
            'groupid' => $group1
        ]);
        error_log("Happiness " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        self::assertEquals(3, count($ret['members']));

        $m->delete("UT delete");

        error_log(__METHOD__ . " end");
    }

    public function testIntendedTaken()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail($email);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $origmsg = file_get_contents('msgs/basic');
        $msg = $this->unique($origmsg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Basic test', 'OFFER: a thing (A Place)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'OutcomeIntended',
            'outcome' => Message::OUTCOME_TAKEN
        ]);

        # Too soon.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals(0, $m->processIntendedOutcomes($id));

        # Now make it look older.
        $this->dbhm->preExec("UPDATE messages_outcomes_intended SET timestamp = DATE_SUB(timestamp, INTERVAL 3 HOUR) WHERE msgid = ?;", [ $id ]);
        assertEquals(1, $m->processIntendedOutcomes($id));
        $atts = $m->getPublic();
        assertEquals(Message::OUTCOME_TAKEN, $atts['outcomes'][0]['outcome']);

        $m->delete("UT delete");

        error_log(__METHOD__ . " end");
    }

    public function testIntendedReceived()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail($email);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $origmsg = file_get_contents('msgs/basic');
        $msg = $this->unique($origmsg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Basic test', 'WANTED: a thing (A Place)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'OutcomeIntended',
            'outcome' => Message::OUTCOME_RECEIVED
        ]);

        # Too soon.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals(0, $m->processIntendedOutcomes($id));

        # Now make it look older.
        $this->dbhm->preExec("UPDATE messages_outcomes_intended SET timestamp = DATE_SUB(timestamp, INTERVAL 3 HOUR) WHERE msgid = ?;", [ $id ]);
        assertEquals(1, $m->processIntendedOutcomes($id));
        $atts = $m->getPublic();
        assertEquals(Message::OUTCOME_RECEIVED, $atts['outcomes'][0]['outcome']);

        $m->delete("UT delete");

        error_log(__METHOD__ . " end");
    }

    public function testIntendedWithdrawn()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail($email);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $origmsg = file_get_contents('msgs/basic');
        $msg = $this->unique($origmsg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Basic test', 'WANTED: a thing (A Place)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'OutcomeIntended',
            'outcome' => Message::OUTCOME_WITHDRAWN
        ]);

        # Too soon.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals(0, $m->processIntendedOutcomes($id));

        # Now make it look older.
        $this->dbhm->preExec("UPDATE messages_outcomes_intended SET timestamp = DATE_SUB(timestamp, INTERVAL 3 HOUR) WHERE msgid = ?;", [ $id ]);
        assertEquals(1, $m->processIntendedOutcomes($id));
        $atts = $m->getPublic();
        assertEquals(Message::OUTCOME_WITHDRAWN, $atts['outcomes'][0]['outcome']);

        $m->delete("UT delete");

        error_log(__METHOD__ . " end");
    }


    public function testIntendedRepost()
    {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail($email);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $origmsg = file_get_contents('msgs/basic');
        $msg = $this->unique($origmsg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('Basic test', 'WANTED: a thing (A Place)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'action' => 'OutcomeIntended',
            'outcome' => 'Repost'
        ]);

        $groups = $m->getGroups(FALSE, FALSE);
        $arrival = strtotime($groups[0]['arrival']);

        # Too soon.
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals(0, $m->processIntendedOutcomes($id));
        sleep(5);

        # Now make it look older.
        $this->dbhm->preExec("UPDATE messages_outcomes_intended SET timestamp = DATE_SUB(timestamp, INTERVAL 3 HOUR) WHERE msgid = ?;", [ $id ]);
        assertEquals(1, $m->processIntendedOutcomes($id));
        $atts = $m->getPublic();
        assertEquals(0, count($atts['outcomes']));

        $groups = $m->getGroups(FALSE, FALSE);
        $arrival2 = strtotime($groups[0]['arrival']);
        assertGreaterThan($arrival, $arrival2);

        $m->delete("UT delete");

        error_log(__METHOD__ . " end");
    }
}