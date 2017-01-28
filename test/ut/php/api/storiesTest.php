<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Story.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class storiesAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users_stories WHERE headline LIKE 'Test%';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_REUSE);
        $u->addMembership($this->groupid);

        # Create logged out - should fail
        $ret = $this->call('stories', 'PUT', [
            'headline' => 'Test',
            'story' => 'Test'
        ]);
        assertEquals(1, $ret['ret']);

        # Create logged in - should work
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('stories', 'PUT', [
            'headline' => 'Test',
            'story' => 'Test'
        ]);
        assertEquals(0, $ret['ret']);

        $id = $ret['id'];
        assertNotNull($id);

        # Get with id - should work
        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['story']['id']);
        self::assertEquals('Test', $ret['story']['headline']);
        self::assertEquals('Test', $ret['story']['story']);

        # Edit
        $ret = $this->call('stories', 'PATCH', [
            'id' => $id,
            'headline' => 'Test2',
            'story' => 'Test2',
            'public' => 0
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        self::assertEquals('Test2', $ret['story']['headline']);
        self::assertEquals('Test2', $ret['story']['story']);

        # List stories - should be none as we're not a mod.
        $ret = $this->call('stories', 'GET', [ 'groupid' => $this->groupid ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['stories']));

        # Get logged out - should fail, not public
        $_SESSION['id'] = NULL;
        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        assertEquals(2, $ret['ret']);

        # Make us a mod
        $u->addMembership($this->groupid, User::ROLE_MODERATOR);
        assertTrue($this->user->login('testpw'));

        $ret = $this->call('stories', 'GET', [
            'reviewed' => 0
        ]);
        error_log("Get as mod " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['stories']));
        self::assertEquals($id, $ret['stories'][0]['id']);

        # Mark reviewed
        $ret = $this->call('stories', 'PATCH', [
            'id' => $id,
            'reviewed' => 1,
            'public' => 1
        ]);
        assertEquals(0, $ret['ret']);

        # Get logged out - should work.
        $_SESSION['id'] = NULL;
        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['story']['id']);

        # List for this group - should work.
        $ret = $this->call('stories', 'GET', [ 'groupid' => $this->groupid ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['stories'][0]['id']);

        # Delete logged out - should fail
        $_SESSION['id'] = NULL;
        $ret = $this->call('stories', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(2, $ret['ret']);

        assertTrue($this->user->login('testpw'));

        # Delete - should work
        $ret = $this->call('stories', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        # Delete - fail
        $ret = $this->call('stories', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    function testAsk() {
        error_log(__METHOD__);

        # Create the sending user
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        error_log("Created user $uid");
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        assertGreaterThan(0, $u->addEmail('test@test.com'));

        # Send a message.
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Subject: Basic test', 'Subject: [Group-tag] Offer: thing (place)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $origid = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertNotNull($origid);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Shouldn't yet appear.
        $s = new Story($this->dbhr, $this->dbhm);
        self::assertEquals(0, $s->askForStories('2017-01-01', $uid, 0, NULL));

        # Now mark the message as complete
        error_log("Mark $origid as TAKEN");
        $m = new Message($this->dbhr, $this->dbhm, $origid);
        $m->mark(Message::OUTCOME_TAKEN, "Thanks", User::HAPPY, $uid);

        # Now should ask.
        self::assertEquals(1, $s->askForStories('2017-01-01', $uid, 0, NULL));

        # But not a second time
        self::assertEquals(0, $s->askForStories('2017-01-01', $uid, 0, NULL));

        error_log(__METHOD__ . " end");
    }
}

