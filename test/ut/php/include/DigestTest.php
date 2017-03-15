<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/Digest.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class digestTest extends IznikTestCase {
    private $dbhr, $dbhm;

    private $msgsSent = [];

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->msgsSent = [];

        $this->tidy();
    }

    public function sendMock($mailer, $message) {
        $this->msgsSent[] = $message->toString();
    }

    public function testImmediate() {
        error_log(__METHOD__);

        # Mock the actual send
        $mock = $this->getMockBuilder('Digest')
            ->setConstructorArgs([$this->dbhm, $this->dbhm])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        # Create a group with a message on it.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_REUSE);
        $g->setPrivate('onyahoo', 1);
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $msg = str_replace("Hey", "Hey {{username}}", $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Create a user on that group who wants immediate delivery.  They need two emails; one for our membership,
        # and a real one to get the digest.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail('test@blackhole.io');
        $eid = $u->addEmail('test@' . USER_DOMAIN);
        error_log("Created user $uid email $eid");
        assertGreaterThan(0, $eid);
        $u->addMembership($gid, User::ROLE_MEMBER, $eid);
        $u->setMembershipAtt($gid, 'emailfrequency', Digest::IMMEDIATE);

        # Now test.
        assertEquals(1, $mock->send($gid, Digest::IMMEDIATE));
        assertEquals(1, count($this->msgsSent));

        error_log(__METHOD__ . " end");
    }

    public function testSend() {
        error_log(__METHOD__);

        # Actual send for coverage.
        $d = new Digest($this->dbhm, $this->dbhm);

        # Create a group with a message on it.
        $g = Group::get($this->dbhm, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_REUSE);
        $g->setPrivate('onyahoo', 1);
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $msg = str_replace("Hey", "Hey {{username}}", $msg);

        $r = new MailRouter($this->dbhm, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Create a user on that group who wants immediate delivery.  They need two emails; one for our membership,
        # and a real one to get the digest.
        $u = User::get($this->dbhm, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail('test@blackhole.io');
        $eid = $u->addEmail('test@' . USER_DOMAIN);
        error_log("Created user $uid email $eid");
        assertGreaterThan(0, $eid);
        $u->addMembership($gid, User::ROLE_MEMBER, $eid);
        $u->setMembershipAtt($gid, 'emailfrequency', Digest::IMMEDIATE);

        # And another who only has a membership on Yahoo and therefore shouldn't get one.
        $u2 = User::get($this->dbhm, $this->dbhm);
        $uid2 = $u2->create(NULL, NULL, 'Test User');
        $u2->addEmail('test2@blackhole.io');
        error_log("Created user $uid2");
        $u2->addMembership($gid, User::ROLE_MEMBER);
        $u2->setMembershipAtt($gid, 'emailfrequency', Digest::IMMEDIATE);

        # Now test.
        assertEquals(1, $d->send($gid, Digest::IMMEDIATE));

        # Again - nothing to send.
        assertEquals(0, $d->send($gid, Digest::IMMEDIATE));

        # Now add one of our emails to the second user.  Because we've not sync'd this group, we will decide to send
        # an email.
        error_log("Now with our email");
        $eid2 = $u2->addEmail('test2@' . USER_DOMAIN);
        $this->dbhm->preExec("DELETE FROM groups_digests WHERE groupid = ?;", [ $gid ]);

        # Force pick up of new email.
        User::$cache = [];
        assertEquals(2, $d->send($gid, Digest::IMMEDIATE));

        error_log(__METHOD__ . " end");
    }

    public function testError() {
        error_log(__METHOD__);

        # Create a group with a message on it.
        $g = Group::get($this->dbhm, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_REUSE);
        $g->setPrivate('onyahoo', 1);
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $msg = str_replace("Hey", "Hey {{username}}", $msg);

        $r = new MailRouter($this->dbhm, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Create a user on that group who wants immediate delivery.  They need two emails; one for our membership,
        # and a real one to get the digest.
        $u = User::get($this->dbhm, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail('test@blackhole.io');
        $eid = $u->addEmail('test@' . USER_DOMAIN);
        error_log("Created user $uid email $eid");
        assertGreaterThan(0, $eid);
        $u->addMembership($gid, User::ROLE_MEMBER, $eid);
        $u->setMembershipAtt($gid, 'emailfrequency', Digest::IMMEDIATE);

        # And another who only has a membership on Yahoo and therefore shouldn't get one.
        $u2 = User::get($this->dbhm, $this->dbhm);
        $uid2 = $u2->create(NULL, NULL, 'Test User');
        $u2->addEmail('test2@blackhole.io');
        error_log("Created user $uid2");
        $u2->addMembership($gid, User::ROLE_MEMBER);
        $u2->setMembershipAtt($gid, 'emailfrequency', Digest::IMMEDIATE);

        # Mock for coverage.
        $mock = $this->getMockBuilder('Digest')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, NULL, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->willThrowException(new Exception());
        $mock->send($gid, Digest::IMMEDIATE);

        error_log(__METHOD__ . " end");
    }

    public function testMultipleMails() {
        error_log(__METHOD__);

        # Mock the actual send
        $mock = $this->getMockBuilder('Digest')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, NULL, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        # Create a group with two messages on it, one taken.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_REUSE);
        $g->setPrivate('onyahoo', TRUE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test thing (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'TAKEN: Test item (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Create a user on that group who wants immediate delivery.  They need two emails; one for our membership,
        # and a real one to get the digest.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail('test@blackhole.io');
        $eid = $u->addEmail('test@' . USER_DOMAIN);
        error_log("Created user $uid email $eid");
        assertGreaterThan(0, $eid);
        $u->addMembership($gid, User::ROLE_MEMBER, $eid);
        $u->setMembershipAtt($gid, 'emailfrequency', Digest::HOUR1);

        # Now test.
        assertEquals(1, $mock->send($gid, Digest::HOUR1));
        assertEquals(1, count($this->msgsSent));
        
        # Again - nothing to send.
        assertEquals(0, $mock->send($gid, Digest::HOUR1));

        error_log(__METHOD__ . " end");
    }
}

