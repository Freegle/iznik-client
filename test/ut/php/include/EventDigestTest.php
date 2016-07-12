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

        # Mock the actual send
        $mock = $this->getMockBuilder('EventDigest')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, NULL, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        # Create a group with two events on it.
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_REUSE);
        $g->setPrivate('onyahoo', TRUE);

        # And two users, one who wants events and one who doesn't.
        $u = new User($this->dbhr, $this->dbhm);
        $uid1 = $u->create(NULL, NULL, "Test User");
        $u->addEmail('test1@test.com');
        $u->addMembership($gid);
        $u->setMembershipAtt($gid, 'eventsallowed', 0);
        $uid2 = $u->create(NULL, NULL, "Test User");
        $u->addEmail('test2@test.com');
        $u->addMembership($gid);

        $e = new CommunityEvent($this->dbhr, $this->dbhm);
        $e->create($uid1, 'Test Event 1', 'Test Location', 'Test Contact Name', '000 000 000', 'test@test.com', 'A test event');
        $e->addGroup($gid);
        $e->addDate(ISODate('@' . strtotime('next monday 10am')), ISODate('@' . strtotime('next monday 11am')));
        $e->addDate(ISODate('@' . strtotime('next tuesday 10am')), ISODate('@' . strtotime('next tuesday 11am')));

        $e->create($uid1, 'Test Event 2', 'Test Location', 'Test Contact Name', '000 000 000', 'test@test.com', 'A test event');
        $e->addGroup($gid);
        $e->addDate(ISODate('@' . strtotime('next wednesday 2pm')), ISODate('@' . strtotime('next wednesday 3pm')));

        # Now test.
        assertEquals(1, $mock->send($gid));
        assertEquals(1, count($this->eventsSent));

        error_log("Mail sent" . var_export($this->eventsSent, TRUE));

        # Turn off
        $mock->off($uid2, $gid);

        assertEquals(0, $mock->send($gid));

        # Invalid email
        $uid3 = $u->create(NULL, NULL, "Test User");
        $u->addEmail('test.com');
        $u->addMembership($gid);
        assertEquals(0, $mock->send($gid));

        # Actual send for coverage.
        $uid4 = $u->create(NULL, NULL, "Test User");
        $u->addEmail('test@blackhole.io');
        $u->addMembership($gid);
        $e = new EventDigest($this->dbhr, $this->dbhm);
        $e->send($gid);

        error_log(__METHOD__ . " end");
    }
}

