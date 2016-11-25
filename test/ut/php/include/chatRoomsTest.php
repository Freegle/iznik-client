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

        $g = Group::get($dbhr, $dbhm);
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

        $r->setAttributes(['name' => 'test']);
        assertEquals('testgroup Mods', $r->getPublic()['name']);
        
        assertEquals(1, $r->delete());

        error_log(__METHOD__ . " end");
    }

    public function testConversation() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
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

        $id = $r->createGroupChat('test', $this->groupid);
        assertNull($id);

        error_log(__METHOD__ . " end");
    }
    
    public function testNotifyUser2User() {
        error_log(__METHOD__ );

        # Set up a chatroom
        $u = User::get($this->dbhr, $this->dbhm);
        $u1 = $u->create(NULL, NULL, "Test User 1");
        $u->addMembership($this->groupid);
        $u->addEmail('test1@test.com');
        $u->addEmail('test1@' . USER_DOMAIN);
        $u2 = $u->create(NULL, NULL, "Test User 2");
        $u->addMembership($this->groupid);
        $u->addEmail('test2@test.com');
        $u->addEmail('test2@' . USER_DOMAIN);

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

        # Exception first for coverage.
        error_log("Fake exception");
        $r = $this->getMockBuilder('ChatRoom')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('constructMessage'))
            ->getMock();

        $r->method('constructMessage')->willThrowException(new Exception());

        assertEquals(0, $r->notifyByEmail($id, ChatRoom::TYPE_USER2USER, 0));

        # We will have flagged this message as mailed to all even though we failed.
        $this->dbhm->preExec("UPDATE chat_messages SET mailedtoall = 0 WHERE id = ?;", [ $cm ]);

        $r = $this->getMockBuilder('ChatRoom')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('mailer'))
            ->getMock();
        
        $r->method('mailer')->will($this->returnCallback(function($message) {
            return($this->mailer($message));
        }));

        $this->msgsSent = [];

        # Notify - will email both.
        error_log("Will email both");
        assertEquals(2, $r->notifyByEmail($id, ChatRoom::TYPE_USER2USER, 0));
        assertEquals('Re: OFFER: Test item (location)', $this->msgsSent[0]['subject']);

        # Now pretend we've seen the messages.  Should flag the message as seen by all.
        $r->updateRoster($u1, $cm, ChatRoom::STATUS_ONLINE);
        $r->updateRoster($u2, $cm, ChatRoom::STATUS_ONLINE);
        $m = new ChatMessage($this->dbhr, $this->dbhm, $cm);
        assertEquals(1, $m->getPrivate('seenbyall'));

        # Shouldn't notify as we've seen them.
        $r->expects($this->never())->method('mailer');
        assertEquals(0, $r->notifyByEmail($id,  ChatRoom::TYPE_USER2USER));

        # Once more for luck - this time won't even check this chat.
        assertEquals(0, $r->notifyByEmail($id,  ChatRoom::TYPE_USER2USER));
        
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

    public function testUser2Mod() {
        error_log(__METHOD__ );

        # Set up a chatroom
        $u = User::get($this->dbhr, $this->dbhm);
        $u1 = $u->create(NULL, NULL, "Test User 1");
        $u->addEmail('test1@test.com');
        $u->addEmail('test1@' . USER_DOMAIN);
        $u->addMembership($this->groupid, User::ROLE_MEMBER);
        $u2 = $u->create(NULL, NULL, "Test User 2");
        $u->addEmail('test2@test.com');
        $u->addEmail('test2@' . USER_DOMAIN);
        $u->addMembership($this->groupid, User::ROLE_MODERATOR);

        $r = new ChatRoom($this->dbhm, $this->dbhm);
        $id = $r->createUser2Mod($u1, $this->groupid);
        error_log("Chat room $id for $u1 <-> $u2");
        assertNotNull($id);

        $r->delete();

        $dbconfig = array (
            'host' => SQLHOST,
            'port' => SQLPORT,
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willReturn(FALSE);
        $r->setDbhm($mock);

        $id = $r->createUser2Mod($u1, $this->groupid);
        error_log("Chat room $id for $u1 <-> $u2");
        assertNull($id);

        error_log(__METHOD__ . " end");
    }

    private $msgsSent = [];

    public function mailer(Swift_Message $message) {
        error_log("Send " . $message->getSubject() . " to " . var_export($message->getTo(), TRUE));
        $this->msgsSent[] = [
            'subject' => $message->getSubject(),
            'to' => $message->getTo()
        ];
    }

    public function testNotifyUser2Mod() {
        error_log(__METHOD__ );

        # Set up a chatroom
        $u = User::get($this->dbhr, $this->dbhm);
        $u1 = $u->create(NULL, NULL, "Test User 1");
        $u->addEmail('test1@test.com');
        $u->addEmail('test1@' . USER_DOMAIN);
        $u2 = $u->create(NULL, NULL, "Test User 2");
        $u->addEmail('test2@test.com');
        $u->addEmail('test2@' . USER_DOMAIN);

        $u1u = User::get($this->dbhr, $this->dbhm, $u1);
        $u2u = User::get($this->dbhr, $this->dbhm, $u2);
        $u1u->addMembership($this->groupid, User::ROLE_MEMBER);
        $u2u->addMembership($this->groupid, User::ROLE_OWNER);

        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $id = $r->createUser2Mod($u1, $this->groupid);
        error_log("Chat room $id for $u1 <-> group {$this->groupid}");
        assertNotNull($id);

        # Create a query from the user to the mods
        $m = new ChatMessage($this->dbhr, $this->dbhm);
        $cm = $m->create($id, $u1, "Help me", ChatMessage::TYPE_DEFAULT, NULL, TRUE);
        error_log("Created chat message $cm");

        $r = $this->getMockBuilder('ChatRoom')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('mailer'))
            ->getMock();

        $r->method('mailer')->will($this->returnCallback(function($message) {
            return($this->mailer($message));
        }));

        # Notify mod; will also notify user
        $this->msgsSent = [];
        assertEquals(2, $r->notifyByEmail($id, ChatRoom::TYPE_USER2MOD, 0));
        assertEquals("Your conversation with the testgroup volunteers", $this->msgsSent[0]['subject']);
        assertEquals("Member conversation on testgroup with Test User 1 (test1@test.com)", $this->msgsSent[1]['subject']);

        # Chase up mods after unreasonably short interval
        self::assertEquals(1, count($r->chaseupMods($id, 0)));

        # Fake mod reply
        $cm2 = $m->create($id, $u2, "Here's some help", ChatMessage::TYPE_DEFAULT, NULL, TRUE);

        # Notify user; this will also copy the mod.
        $this->dbhm->preExec("UPDATE chat_roster SET lastemailed = NULL WHERE userid = ?;", [ $u1 ]);
        $this->msgsSent = [];
        assertEquals(2, $r->notifyByEmail($id, ChatRoom::TYPE_USER2MOD, 0));
        assertEquals("Your conversation with the testgroup volunteers", $this->msgsSent[0]['subject']);
        assertEquals("Member conversation on testgroup with Test User 1 (test1@test.com)", $this->msgsSent[1]['subject']);

        error_log(__METHOD__ . " end");
    }
}


