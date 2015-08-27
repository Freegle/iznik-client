<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class MailRouterTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        # Tidy up any old test messages.
        $this->dbhm->preExec("DELETE FROM messages_incoming WHERE fromaddr = ? OR fromip = ?;", ['from@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM messages_approved WHERE fromaddr = ? OR fromip = ?;", ['from@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE fromaddr = ? OR fromip = ?;", ['from@test.com', '1.2.3.4']);
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testSpam() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/spam');
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $r->route();

        error_log(__METHOD__ . " end");
    }

    public function testHam() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::TO_GROUP, $rc);

        $msg = file_get_contents('msgs/fromyahoo');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::TO_GROUP, $rc);

        error_log(__METHOD__ . " end");
    }

    public function testSpamIP() {
        error_log(__METHOD__);

        # Sorry, Cameroon folk.
        $msg = file_get_contents('msgs/cameroon');

        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        # This should have stored the IP in the message.
        error_log("Message ID $id");
        $m = new IncomingMessage($this->dbhm, $this->dbhm, $id);
        assertEquals('41.205.16.153', $m->getFromIP());

        error_log(__METHOD__ . " end");
    }

    public function testFailSpam() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/spam');
        $r = new MailRouter($this->dbhr, $this->dbhm);

        # Make the attempt to move the message fail.
        $mock = $this->getMockBuilder('IncomingMessage')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('delete'))
            ->getMock();
        $mock->method('delete')->willReturn(false);
        $r->setMsg($mock);

        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::FAILURE, $rc);

        # Make the spamc check itself fail
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('spamc')
            ->disableOriginalConstructor()
            ->setMethods(array('filter'))
            ->getMock();
        $mock->method('filter')->willReturn(false);
        $r->setSpamc($mock);

        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::FAILURE, $rc);

        # Make the geo lookup throw an exception, which it does for unknown IPs
        $msg = file_get_contents('msgs/basic');
        $msg = str_replace('X-Originating-IP: 1.2.3.4', 'X-Originating-IP: 238.162.112.228', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);

        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::TO_GROUP, $rc);

        error_log(__METHOD__ . " end");
    }

    public function testFailHam() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $r = new MailRouter($this->dbhr, $this->dbhm);

        # Make the attempt to move the message fail.
        $mock = $this->getMockBuilder('IncomingMessage')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('delete'))
            ->getMock();
        $mock->method('delete')->willReturn(false);
        $r->setMsg($mock);

        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::FAILURE, $rc);

        # Make the spamc check itself fail
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('spamc')
            ->disableOriginalConstructor()
            ->setMethods(array('filter'))
            ->getMock();
        $mock->method('filter')->willReturn(false);
        $r->setSpamc($mock);

        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::FAILURE, $rc);

        error_log(__METHOD__ . " end");
    }

    public function testMultipleUsers() {
        error_log(__METHOD__);

        for ($i = 0; $i < Spam::USER_THRESHOLD + 2; $i++) {
            error_log("User $i");

            $msg = file_get_contents('msgs/basic');
            $msg = str_replace(
                'From: "Test User" <test@test.com>',
                'From: "Test User" <test' . $i . '@test.com>',
                $msg);

            $r = new MailRouter($this->dbhr, $this->dbhm);
            $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
            $rc = $r->route();

            if ($i < Spam::USER_THRESHOLD) {
                assertEquals(MailRouter::TO_GROUP, $rc);
            } else {
                assertEquals(MailRouter::INCOMING_SPAM, $rc);
            }
        }

        error_log(__METHOD__ . " end");
    }

    public function testMultipleGroups() {
        error_log(__METHOD__);

        for ($i = 0; $i < Spam::GROUP_THRESHOLD + 2; $i++) {
            error_log("Group $i");

            $msg = file_get_contents('msgs/basic');

            $msg = str_replace(
                'To: "freegleplayground@yahoogroups.com" <freegleplayground@yahoogroups.com>',
                'To: "freegleplayground' . $i . '@yahoogroups.com" <freegleplayground' . $i . '@yahoogroups.com>',
                $msg);

            $r = new MailRouter($this->dbhr, $this->dbhm);
            $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
            $rc = $r->route();

            if ($i < Spam::GROUP_THRESHOLD) {
                assertEquals(MailRouter::TO_GROUP, $rc);
            } else {
                assertEquals(MailRouter::INCOMING_SPAM, $rc);
            }
        }

        error_log(__METHOD__ . " end");
    }

    function testRouteAll() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');

        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->routeAll();

        error_log(__METHOD__ . " end");
    }
}

