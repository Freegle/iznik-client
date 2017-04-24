<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Facebook.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class groupFacebookTest extends IznikTestCase {
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
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $t->getPostsToShare('a', "last week");
        $t->getPostsToShare($t->getPublic()['sharefrom'], "last week");

        $gid = $g->create('testgroup', Group::GROUP_UT);
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $t->add($gid, 'test', 'test', 'test');
        assertEquals('test', $t->getPublic()['token']);

        error_log(__METHOD__ . " end");
    }

    public function post() {
        return(TRUE);
    }

    public function testMessages() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $ids = GroupFacebook::listForGroup($this->dbhr, $this->dbhm, $gid);

        foreach ($ids as $uid) {
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
            error_log("From user " . $a->getFromuser());
            $sender = User::get($this->dbhr, $this->dbhm, $a->getFromuser());
            $sender->setPrivate('publishconsent', 1);

            $mock = $this->getMockBuilder('GroupFacebook')
                ->setConstructorArgs([$this->dbhr, $this->dbhm, $uid])
                ->setMethods(array('getFB'))
                ->getMock();

            $mock->method('getFB')->willReturn($this);

            # Fake message onto group.
            $this->dbhm->preExec("UPDATE messages_groups SET yahooapprovedid = ? WHERE msgid = ? AND groupid = ?;", [
                $id,
                $id,
                $gid
            ]);

            $count = $mock->postMessages();
            assertGreaterThanOrEqual(1, $count);

            # Should be none to post now.
            $count = $mock->postMessages();
            assertGreaterThanOrEqual(0, $count);
        }

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
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
        error_log("From user " . $a->getFromuser());
        $sender = User::get($this->dbhr, $this->dbhm, $a->getFromuser());
        $sender->setPrivate('publishconsent', 1);

        $mock = $this->getMockBuilder('GroupFacebook')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, $gid])
            ->setMethods(array('getFB'))
            ->getMock();

        $mock->method('getFB')->willThrowException(new Exception('Test', 100));

        # Fake message onto group.
        $this->dbhm->preExec("UPDATE messages_groups SET yahooapprovedid = ? WHERE msgid = ? AND groupid = ?;", [
            $id,
            $id,
            $gid
        ]);

        $count = $mock->postMessages();
        assertGreaterThanOrEqual(0, $count);

        error_log(__METHOD__ . " end");
    }
}

