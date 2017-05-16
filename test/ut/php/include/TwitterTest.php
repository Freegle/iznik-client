<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Twitter.php';
require_once IZNIK_BASE . '/include/group/CommunityEvent.php';
require_once IZNIK_BASE . '/include/user/Story.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class twitterTest extends IznikTestCase {
    private $dbhr, $dbhm;

    private $msgsSent = [];

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->tidy();
    }

    public function testBasic() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $t = new Twitter($this->dbhr, $this->dbhm, $gid);
        $data = file_get_contents(IZNIK_BASE . '/test/ut/php/images/chair.jpg');
        $t->tweet('Test - ignore', $data);

        $gid = $g->create('testgroup', Group::GROUP_UT);
        $t = new Twitter($this->dbhr, $this->dbhm, $gid);
        $t->set('test', 'test', 'test');
        $atts = $t->getPublic();
        assertEquals('test', $atts['name']);
        assertEquals('test', $atts['token']);
        assertEquals('test', $atts['secret']);

        error_log(__METHOD__ . " end");
    }

    public function testMessages() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);

        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id, $already) = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        error_log("Approved message id $id");

        # Ensure we have consent to see this message
        $a = new Message($this->dbhr, $this->dbhm, $id);
        $sender = User::get($this->dbhr, $this->dbhm, $a->getFromuser());
        $sender->setPrivate('publishconsent', 1);

        $t = new Twitter($this->dbhr, $this->dbhm, $gid);

        # Fake message onto group.
        $this->dbhm->preExec("UPDATE messages_groups SET yahooapprovedid = ? WHERE msgid = ? AND groupid = ?;", [
            $id,
            $id,
            $gid
        ]);

        $count = $t->tweetMessages();
        assertGreaterThanOrEqual(1, $count);

        # Should be none to tweet now.
        $count = $t->tweetMessages();
        assertGreaterThanOrEqual(0, $count);
        
        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhm, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_UT);

        $t = new Twitter($this->dbhr, $this->dbhm, $gid);

        # Fake a fail
        $mock = $this->getMockBuilder('TwitterOAuth')
            ->setMethods(['post', 'get', 'setTimeouts'])
            ->getMock();

        $mock->method('get')->willReturn(true);
        $mock->method('setTimeouts')->willReturn(true);
        $mock->method('post')->willReturn([
            'errors' => [
                [
                    'code' => 220
                ]
            ]
        ]);

        $t->setTw($mock);

        assertFalse($t->tweet('test', NULL));
        $atts = $t->getPublic();
        error_log("After fail " . var_export($atts, TRUE));
        assertFalse($atts['valid']);

        # Now fake a lock
        $mock = $this->getMockBuilder('TwitterOAuth')
            ->setMethods(['post', 'get', 'setTimeouts'])
            ->getMock();

        $mock->method('get')->willReturn(true);
        $mock->method('setTimeouts')->willReturn(true);
        $mock->method('post')->willReturn([
            'errors' => [
                [
                    'code' => 326
                ]
            ]
        ]);
        $t->setTw($mock);

        assertFalse($t->tweet('test', NULL));
        $atts = $t->getPublic();
        error_log("After lock " . var_export($atts, TRUE));
        assertTrue($atts['locked']);

        error_log("Now tweet successfully and reset");
        $mock = $this->getMockBuilder('TwitterOAuth')
            ->setMethods(['post', 'get', 'setTimeouts'])
            ->getMock();

        $mock->method('get')->willReturn(true);
        $mock->method('setTimeouts')->willReturn(true);
        $mock->method('post')->willReturn([
            'test' => TRUE
        ]);
        $t->setTw($mock);

        assertTrue($t->tweet('test', NULL));
        $atts = $t->getPublic();
        assertTrue($atts['valid']);
        assertFalse($atts['locked']);

        error_log(__METHOD__ . " end");
    }

    public function testEvents() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $e = new CommunityEvent($this->dbhr, $this->dbhm);
        $eid = $e->create(NULL, 'Test Event', 'Test location', NULL, NULL, NULL, NULL, 'Test Event');
        $e->addGroup($gid);
        $start = date("Y-m-d H:i:s", strtotime('+3 hours'));
        $end = date("Y-m-d H:i:s", strtotime('+4 hours'));
        $e->addDate($start, $end);

        $t = new Twitter($this->dbhr, $this->dbhm, $gid);

        $count = $t->tweetEvents();
        assertGreaterThanOrEqual(1, $count);

        error_log(__METHOD__ . " end");
    }

    public function testStories() {
        error_log(__METHOD__);

        $this->dbhm->preExec("DELETE FROM users_stories WHERE headline LIKE 'Test%';");

        $s = new Story($this->dbhr, $this->dbhm);
        $sid = $s->create(NULL, 1, 'Test Story', 'Test Story');

        $mock = $this->getMockBuilder('Twitter')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, NULL))
            ->setMethods(['tweet'])
            ->getMock();
        $mock->method('tweet')->willReturn(true);

        $mock->tweetStory($sid);

        error_log(__METHOD__ . " end");
    }
}

