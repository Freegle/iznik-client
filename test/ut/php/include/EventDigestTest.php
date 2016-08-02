<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/CommunityEvent.php';
require_once IZNIK_BASE . '/include/mail/EventDigest.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class eventDigestTest extends IznikTestCase {
    private $dbhr, $dbhm;

    private $eventsSent = [];

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
        $this->eventsSent[] = $message->toString();
    }

    public function testEvents() {
        error_log(__METHOD__);

        # Create a group with two events on it.
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_REUSE);
        $g->setPrivate('onyahoo', TRUE);


        # And two users, one who wants events and one who doesn't.
        $u = new User($this->dbhr, $this->dbhm);
        $uid1 = $u->create(NULL, NULL, "Test User");
        $eid1 = $u->addEmail('test1@test.com');
        $u->addEmail('test1@' . USER_DOMAIN);
        $u->addMembership($gid, User::ROLE_MEMBER, $eid1);
        $u->setMembershipAtt($gid, 'eventsallowed', 0);
        $uid2 = $u->create(NULL, NULL, "Test User");
        $eid2 = $u->addEmail('test2@test.com');
        $u->addEmail('test2@' . USER_DOMAIN);
        $u->addMembership($gid, User::ROLE_MEMBER, $eid2);

        $e = new CommunityEvent($this->dbhr, $this->dbhm);
        $e->create($uid1, 'Test Event 1', 'Test Location', 'Test Contact Name', '000 000 000', 'test@test.com', 'A test event');
        $e->addGroup($gid);
        $e->addDate(ISODate('@' . strtotime('next monday 10am')), ISODate('@' . strtotime('next monday 11am')));
        $e->addDate(ISODate('@' . strtotime('next tuesday 10am')), ISODate('@' . strtotime('next tuesday 11am')));

        $e->create($uid1, 'Test Event 2', 'Test Location', 'Test Contact Name', '000 000 000', 'test@test.com', 'A test event');
        $e->addGroup($gid);
        $e->addDate(ISODate('@' . strtotime('next wednesday 2pm')), ISODate('@' . strtotime('next wednesday 3pm')));

        # Now test.

        # Send fails
        $mock = $this->getMockBuilder('EventDigest')
            ->setConstructorArgs([$this->dbhm, $this->dbhm, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->willThrowException(new Exception());
        assertEquals(0, $mock->send($gid));

        # Mock the actual send
        $mock = $this->getMockBuilder('EventDigest')
            ->setConstructorArgs([$this->dbhm, $this->dbhm, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));
        assertEquals(1, $mock->send($gid));
        assertEquals(1, count($this->eventsSent));

        error_log("Mail sent" . var_export($this->eventsSent, TRUE));

        # Actual send for coverage.
        $d = new EventDigest($this->dbhr, $this->dbhm);
        assertEquals(1, $d->send($gid));

        # Turn off
        $mock->off($uid2, $gid);

        assertEquals(0, $mock->send($gid));

        # Invalid email
        $uid3 = $u->create(NULL, NULL, "Test User");
        $u->addEmail('test.com');
        $u->addMembership($gid);
        assertEquals(0, $mock->send($gid));

        error_log("For coverage");
        $e = new EventDigest($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('SwiftMailer')
            ->setMethods(array('send'))
            ->getMock();
        $mock->method('send')->willThrowException(new Exception());
        try {
            $e->sendOne($mock, NULL);
            assertTrue(FALSE);
        } catch (Exception $e){}

        error_log(__METHOD__ . " end");
    }
}

