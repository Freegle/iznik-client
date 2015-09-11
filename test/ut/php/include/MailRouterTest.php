<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/SpamMessage.php';
require_once IZNIK_BASE . '/include/message/PendingMessage.php';

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
        $this->dbhm->preExec("DELETE FROM messages_incoming WHERE fromaddr = ? OR fromip = ?;", ['test@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM messages_approved WHERE fromaddr = ? OR fromip = ?;", ['test@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE fromaddr = ? OR fromip = ?;", ['test@test.com', '1.2.3.4']);
        $this->dbhm->preExec("DELETE FROM groups WHERE nameshort LIKE 'testgroup%';", []);
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';", []);

        # Whitelist this IP
        $this->dbhm->preExec("INSERT INTO spam_whitelist_ips (ip, comment) VALUES ('1.2.3.4', 'UT whitelist');", []);
    }

    protected function tearDown() {
        parent::tearDown ();

        $this->dbhm->preExec("DELETE FROM spam_whitelist_ips WHERE ip = '1.2.3.4';", []);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE fromip = '1.2.3.4';", []);
        $this->dbhm->preExec("DELETE FROM messages_history WHERE fromip = '4.3.2.1';", []);
    }

    public function __construct() {
    }

    public function testSpamSubject() {
        error_log(__METHOD__);

        $subj = "Test spam subject " . microtime();
        $groups = [];
        $r = new MailRouter($this->dbhr, $this->dbhm);

        for ($i = 0; $i < Spam::SUBJECT_THRESHOLD + 2; $i++) {
            $g = new Group($this->dbhr, $this->dbhm);
            $g->create("testgroup$i", Group::GROUP_REUSE);
            $groups[] = $g;

            $msg = file_get_contents('msgs/basic');
            $msg = str_replace('Basic test', $subj, $msg);
            $msg = str_replace('To: "freegleplayground@yahoogroups.com" <freegleplayground@yahoogroups.com>',
                    "To: \"testgroup$i\" <testgroup$i@yahoogroups.com>",
                    $msg);

            $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
            $rc = $r->route();

            if ($i < Spam::SUBJECT_THRESHOLD - 1) {
                assertEquals(MailRouter::APPROVED, $rc);
            } else {
                assertEquals(MailRouter::INCOMING_SPAM, $rc);
            }
        }

        foreach ($groups as $group) {
            $group->delete();
        }

        error_log(__METHOD__ . " end");
    }

    public function testSpam() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/spam');
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $m = new IncomingMessage($this->dbhr, $this->dbhm, $id);
        assertEquals(IncomingMessage::YAHOO_APPROVED, $m->getSource());

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        assertNull(SpamMessage::findByIncomingId($this->dbhr, -1));
        $spamid = SpamMessage::findByIncomingId($this->dbhr, $id);
        $spam = new SpamMessage($this->dbhr, $this->dbhm, $spamid);
        assertEquals('sender@example.net', $spam->getFromaddr());
        assertNull($spam->getFromIP());
        assertNull($spam->getFromhost());
        assertNull($spam->getGroupID());
        assertEquals($spamid, $spam->getID());
        assertEquals('GTUBE1.1010101@example.net', $spam->getMessageID());
        assertEquals($msg, $spam->getMessage());
        assertEquals(IncomingMessage::YAHOO_APPROVED, $spam->getSource());
        assertEquals('from@test.com', $spam->getEnvelopefrom());
        assertEquals('to@test.com', $spam->getEnvelopeto());
        assertNotNull($spam->getTextbody());
        assertNull($spam->getHtmlbody());
        assertEquals($spam->getSubject(), $spam->getHeader('subject'));
        assertEquals('recipient@example.net', $spam->getTo()[0]['address']);
        assertEquals('Sender', $spam->getFromname());
        assertEquals('SpamAssassin flagged this as likely spam; score 1000 (high is bad)', $spam->getReason());
        $spam->delete();

        error_log(__METHOD__ . " end");
    }

    public function testWhitelist() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/spam');
        $msg = str_replace('Precedence: junk', 'X-Freegle-IP: 1.2.3.4', $msg);
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::INCOMING_SPAM, $rc);

        error_log(__METHOD__ . " end");
    }

    public function testPending() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        
        assertNull(PendingMessage::findByIncomingId($this->dbhr, -1));
        $pendid = PendingMessage::findByIncomingId($this->dbhr, $id);
        assertNotNull($pendid);
        error_log("Found $id in pending $pendid");
        $pend = new PendingMessage($this->dbhr, $this->dbhm, $pendid);
        assertEquals('test@test.com', $pend->getFromaddr());
        assertNull($pend->getFromIP()); # Because whitelisted IPs are masked out
        assertNull($pend->getFromhost());
        assertNotNull($pend->getGroupID());
        assertEquals($pendid, $pend->getID());
        assertEquals('emff7a66f1-e0ed-4792-b493-17a75d806a30@edward-x1', $pend->getMessageID());
        assertEquals($msg, $pend->getMessage());
        assertEquals(IncomingMessage::YAHOO_PENDING, $pend->getSource());
        assertEquals('from@test.com', $pend->getEnvelopefrom());
        assertEquals('to@test.com', $pend->getEnvelopeto());
        assertNotNull($pend->getTextbody());
        assertNotNull($pend->getHtmlbody());
        assertEquals($pend->getSubject(), $pend->getHeader('subject'));
        assertEquals('freegleplayground@yahoogroups.com', $pend->getTo()[0]['address']);
        assertEquals('Test User', $pend->getFromname());
        $pend->delete();

        error_log(__METHOD__ . " end");
    }


    function testPendingToApproved() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        assertNotNull(new PendingMessage($this->dbhr, $this->dbhm, $id));
        error_log("Pending id $id");

        $msg = file_get_contents('msgs/basic');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        assertNull((new PendingMessage($this->dbhr, $this->dbhm, $id))->getMessage());

        # Now the same, but with a TN post which has no messageid.
        error_log("Now TN post");
        $msg = file_get_contents('msgs/tn');
        $m = new IncomingMessage($this->dbhr, $this->dbhm);
        $m->parse(IncomingMessage::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals('20065945', $m->getTnpostid());
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        assertNotNull(new PendingMessage($this->dbhr, $this->dbhm, $id));
        error_log("Pending id $id");

        $msg = file_get_contents('msgs/tn');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        assertNull((new PendingMessage($this->dbhr, $this->dbhm, $id))->getMessage());

        error_log(__METHOD__ . " end");
    }
    public function testHam() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = file_get_contents('msgs/fromyahoo');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = file_get_contents('msgs/basic');
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $r->received(IncomingMessage::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

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
        assertNull($m->getFromIP());

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
        assertEquals(MailRouter::APPROVED, $rc);

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

        $r->received(IncomingMessage::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
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
                'From: "Test User ' . $i . '" <test' . $i . '@test.com>',
                $msg);
            $msg = str_replace('1.2.3.4', '4.3.2.1', $msg);

            $r = new MailRouter($this->dbhr, $this->dbhm);
            $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
            $rc = $r->route();

            if ($i < Spam::USER_THRESHOLD) {
                assertEquals(MailRouter::APPROVED, $rc);
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
            $msg = str_replace('1.2.3.4', '4.3.2.1', $msg);

            $r = new MailRouter($this->dbhr, $this->dbhm);
            $r->received(IncomingMessage::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
            $rc = $r->route();

            if ($i < Spam::GROUP_THRESHOLD) {
                assertEquals(MailRouter::APPROVED, $rc);
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

