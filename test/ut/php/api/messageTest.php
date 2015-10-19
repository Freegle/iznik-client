<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/Collection.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class messageTest extends IznikAPITest {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testApproved() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);

        # Should be able to see this message even logged out.
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Approved'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        # When logged in should be able to see message history.
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $atts = $a->getPublic();
        assertEquals(1, count($atts['fromuser']['messagehistory']));
        assertEquals($id, $atts['fromuser']['messagehistory'][0]['id']);
        assertEquals('Other', $atts['fromuser']['messagehistory'][0]['type']);
        assertEquals('Basic test', $atts['fromuser']['messagehistory'][0]['subject']);

        $a->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testPending() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/basic');
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
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Pending'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to mod - should be able to see it.
        $u->setRole(User::ROLE_MODERATOR, $group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Pending'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        $a->delete();

        error_log(__METHOD__ . " end");
    }

    public function testSpam() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/spam');
        $msg = str_ireplace('To: Recipient <recipient@example.net>', 'To: "testgroup@yahoogroups.com" <testgroup@yahoogroups.com>', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        error_log("Created spam message $id");
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals($id, $a->getID());
        assertTrue(array_key_exists('subject', $a->getPublic()));

        # Shouldn't be able to see spam logged out
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Spam'
        ]);
        assertEquals(1, $ret['ret']);

        # Now join - shouldn't be able to see a spam message as user
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Spam'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to see it.
        $u->setRole(User::ROLE_OWNER, $group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);

        # Delete it - as a user should fail
        $u->setRole(User::ROLE_MEMBER, $group1);
        $ret = $this->call('message', 'DELETE', [
            'id' => $id,
            'collection' => 'Approved'
        ]);
        assertEquals(2, $ret['ret']);

        $u->setRole(User::ROLE_OWNER, $group1);
        $ret = $this->call('message', 'DELETE', [
            'id' => $id,
            'collection' => 'Spam'
        ]);
        assertEquals(0, $ret['ret']);

        # Try again to see it - should be gone
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Spam'
        ]);
        assertEquals(3, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testApprove() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
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
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Approve'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to approve it.  Suppress the mail.
        $u->setRole(User::ROLE_OWNER, $group1);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Approve',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

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

    public function testReject() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_OTHER);

        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

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
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reject'
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to reject it.  Suppress the mail.
        $u->setRole(User::ROLE_OWNER, $group1);

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Reject',
            'subject' => 'Test reject',
            'body' => 'Test body',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        # Plugin work should exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
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

        error_log(__METHOD__ . " end");
    }

    public function testDelete() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        $this->dbhm->preExec("UPDATE messages_groups SET yahooreject = 'test@test.com', yahoopendingid = 1 WHERE msgid = $id;");

        # Shouldn't be able to delete logged out
        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Delete'
        ]);
        assertEquals(2, $ret['ret']);

        # Now join - shouldn't be able to delete as a member
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'Delete'
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

    public function testNotSpam() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        $msg = file_get_contents('msgs/spam');
        $msg = str_ireplace('To: Recipient <recipient@example.net>', 'To: "testgroup@yahoogroups.com" <testgroup@yahoogroups.com>', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
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
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $u->addMembership($group1);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $ret = $this->call('message', 'POST', [
            'id' => $id,
            'groupid' => $group1,
            'action' => 'NotSpam'
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

        error_log(__METHOD__ . " end");
    }
}

