<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/ChatRoom.php';
require_once IZNIK_BASE . '/include/chat/ChatMessage.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class chatMessagesTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

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
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testGroup() {
        error_log(__METHOD__);

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createGroupChat('test', $this->groupid);
        assertNotNull($id);

        $m = new ChatMessage($this->dbhr, $this->dbhm);
        $mid = $m->create($id, $this->uid, 'Test');
        assertNotNull($mid);

        $atts = $m->getPublic();
        assertEquals($id, $atts['chatid']);
        assertEquals('Test', $atts['message']);
        assertEquals($this->uid, $atts['userid']);

        $mid2 = $m->create($id, $this->uid, 'Test2');
        assertNotNull($mid2);
        list($msgs, $users) = $r->getMessages();
        assertEquals(2, count($msgs));
        error_log("Msgs " . var_export($msgs, TRUE));
        assertTrue($msgs[0]['sameasnext']);
        assertTrue($msgs[1]['sameaslast']);

        assertEquals(1, $m->delete());
        assertEquals(1, $r->delete());

        error_log(__METHOD__ . " end");
    }

    public function testSpamReply() {
        error_log(__METHOD__);

        # Put a valid message on a group.
        error_log("Put valid message on");
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_UT);

        $msg = $this->unique(file_get_contents('msgs/offer'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $refmsgid = $r->received(Message::YAHOO_APPROVED, 'test@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Now reply to it with spam.
        error_log("Reply with spam");
        $msg = $this->unique(file_get_contents('msgs/spamreply'));
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $refmsgid = $r->received(Message::EMAIL, 'from2@test.com', 'test@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::TO_USER, $rc);

        error_log(__METHOD__ . " end");
    }

    public function testPairing() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_UT);

        $msg = $this->unique(file_get_contents('msgs/offer'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('OFFER: a test item (location)', 'OFFER: A spade and broom handle (Conniburrow MK14', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $refmsgid1 = $r->received(Message::YAHOO_APPROVED, 'test@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/offer'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('OFFER: a test item (location)', 'Wanted: bike (Conniburrow MK14', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $refmsgid2 = $r->received(Message::YAHOO_APPROVED, 'test@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/replytext'));
        $msg = str_replace('Re: Basic test', 'Re: A spade and broom handle (Conniburrow MK14)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $refmsgid = $r->received(Message::EMAIL, 'from2@test.com', 'test@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::TO_USER, $rc);

        $m = new Message($this->dbhm, $this->dbhm, $refmsgid1);
        $atts = $m->getPublic(FALSE, TRUE, TRUE);
        error_log("Message 1 " . var_export($atts, TRUE));
        assertEquals(1, count($atts['replies']));
        $m = new Message($this->dbhm, $this->dbhm, $refmsgid2);
        $atts = $m->getPublic(FALSE, TRUE, TRUE);
        assertEquals(0, count($atts['replies']));

        error_log(__METHOD__ . " end");
    }

    public function testError() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => SQLHOST,
            'port' => SQLPORT,
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $m = new ChatMessage($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $m->setDbhm($mock);

        $mid = $m->create(NULL, $this->uid, 'Test');
        assertNull($mid);

        error_log(__METHOD__ . " end");
    }

    public function testCheckReview() {
        error_log(__METHOD__);

        $m = new ChatMessage($this->dbhr, $this->dbhm);

        # Fine
        assertFalse($m->checkReview('Nothing to see here'));

        # Spam
        assertTrue($m->checkReview('https://spam'));
        assertTrue($m->checkReview('http://spam'));

        # Valid
        assertFalse($m->checkReview('http://' . USER_DOMAIN));
        assertFalse($m->checkReview('http://freegle.in'));

        # Mixed urls, one valid one not.
        assertTrue($m->checkReview("http://" . USER_DOMAIN . "\r\nhttps://spam.com"));

        # Others.
        assertTrue($m->checkReview('<script'));

        # Keywords
        assertTrue($m->checkReview('spamspamspam'));

        error_log(__METHOD__ . " end");
    }
}


