<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Twitter.php';
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

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $t = new Twitter($this->dbhr, $this->dbhm, $gid);
        $data = file_get_contents(IZNIK_BASE . '/test/ut/php/images/chair.jpg');
        $t->tweet('Test - ignore', $data);

        error_log(__METHOD__ . " end");
    }

    public function testMessages() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $msg = $this->unique(file_get_contents('msgs/attachment'));

        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id, $already) = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        error_log("Approved message id $id");

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
}

