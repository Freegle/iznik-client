<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/chat/ChatRoom.php';
require_once IZNIK_BASE . '/include/chat/ChatMessage.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class chatRoomsTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM chat_rooms WHERE name = 'test';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testGroup() {
        error_log(__METHOD__);

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createGroupChat('test', NULL);
        assertNotNull($id);

        $r->setAttributes(['name' => 'test']);
        assertEquals('test', $r->getPublic()['name']);
        
        assertEquals(1, $r->delete());

        error_log(__METHOD__ . " end");
    }

    public function testConversation() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $u1 = $u->create(NULL, NULL, "Test User 1");
        $u->addEmail('test1@test.com');
        $u2 = $u->create(NULL, NULL, "Test User 2");
        $u->addEmail('test2@test.com');

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createConversation($u1, $u2);
        assertNotNull($id);

        # Further creates should find the same one.
        $id2 = $r->createConversation($u1, $u2);
        assertEquals($id, $id2);

        $id2 = $r->createConversation($u2, $u1);
        assertEquals($id, $id2);

        assertEquals(1, $r->delete());

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

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $r->setDbhm($mock);

        $id = $r->createGroupChat('test');
        assertNull($id);

        error_log(__METHOD__ . " end");
    }
    
    public function testChase() {
        error_log(__METHOD__ );

        # Set up a chatroom
        $u = new User($this->dbhr, $this->dbhm);
        $u1 = $u->create(NULL, NULL, "Test User 1");
        $u->addEmail('test1@test.com');
        $u2 = $u->create(NULL, NULL, "Test User 2");
        $u->addEmail('test2@test.com');

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createConversation($u1, $u2);
        error_log("Chat room $id for $u1 <-> $u2");
        assertNotNull($id);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);

        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($msgid, $already) = $m->save();
        
        $m = new ChatMessage($this->dbhr, $this->dbhm);
        $cm = $m->create($id, $u1, "Testing", ChatMessage::TYPE_DEFAULT, $msgid, TRUE);
        error_log("Created chat message $cm");
        
        $r = $this->getMockBuilder('ChatRoom')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm))
            ->setMethods(array('mailer'))
            ->getMock();
        
        $r->method('mailer')->with(
            'test2@test.com',
            'Re: OFFER: Test item (location)');
        
        assertEquals(1, $r->notifyByEmail($id, TRUE, 0));

        # Now pretend we've seen the messages.  Shouldn't notify as we've seen them, and should end up flagging the
        # message as seen by all.
        $this->dbhm->preExec("UPDATE chat_roster SET lastmsgseen = $msgid WHERE chatid = $id;");
        $r->expects($this->never())->method('mailer');
        assertEquals(0, $r->notifyByEmail($id, TRUE));
        $m = new ChatMessage($this->dbhr, $this->dbhm, $cm);
        assertEquals(1, $m->getPrivate('seenbyall'));

        # Once more for luck - this time won't even check this chat.
        assertEquals(0, $r->notifyByEmail($id), TRUE);
        
        # Now send an email reply to this notification.
        $msg = $this->unique(file_get_contents('msgs/notif_reply_text'));
        $mr = new MailRouter($this->dbhm, $this->dbhm);
        $mid = $mr->received(Message::EMAIL, 'from@test.com', "notify-$id-$u2@" . USER_DOMAIN, $msg);
        $rc = $mr->route();
        assertEquals(MailRouter::TO_USER, $rc);
        $r = new ChatRoom($this->dbhr, $this->dbhm, $id);
        list($msgs, $users) = $r->getMessages();
        error_log("Messages " . var_export($msgs, TRUE));
        assertEquals(ChatMessage::TYPE_DEFAULT, $msgs[1]['type']);
        assertEquals("Ok, here's a reply.", $msgs[1]['message']);
        assertEquals($u2, $msgs[1]['userid']);

        error_log(__METHOD__ . " end");
    }
}


