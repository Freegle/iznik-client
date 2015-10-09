<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/Collection.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class userAPITest extends IznikAPITest {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $dbhm->preExec("DELETE FROM users WHERE yahooUserId = 1;");

        # Create a moderator and log in as them
        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($this->groupid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($this->user->login('testpw'));

        $this->plugin = new Plugin($this->dbhr, $this->dbhm);
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testDeliveryType() {
        error_log(__METHOD__);

        # Shouldn't be able to do this as a member
        $ret = $this->call('user', 'POST', [
            'groupid' => $this->groupid,
            'yahooDeliveryType' => 'DIGEST',
            'email' => 'test@test.com'
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        $ret = $this->call('user', 'POST', [
            'groupid' => $this->groupid,
            'yahooDeliveryType' => 'DIGEST',
            'email' => 'test@test.com',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $work = $this->plugin->get($this->groupid);
        assertEquals(1, count($work));
        $data = json_decode($work[0]['data'], true);
        assertEquals('test@test.com', $data['email']);
        assertEquals('DIGEST', $data['deliveryType']);

        error_log(__METHOD__ . " end");
    }

    public function testPostingStatus() {
        error_log(__METHOD__);

        $ret = $this->call('user', 'POST', [
            'yahooUserId' => 1,
            'groupid' => $this->groupid,
            'yahooPostingStatus' => 'PROHIBITED'
        ]);
        assertEquals(2, $ret['ret']);

        $this->dbhm->preExec("UPDATE users SET yahooUserId = 1 WHERE id = ?;", [ $this->uid ]);

        # Shouldn't be able to do this as a member
        $ret = $this->call('user', 'POST', [
            'yahooUserId' => 1,
            'groupid' => $this->groupid,
            'yahooPostingStatus' => 'PROHIBITED',
            'duplicate' => 0
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        $ret = $this->call('user', 'POST', [
            'yahooUserId' => 1,
            'groupid' => $this->groupid,
            'yahooPostingStatus' => 'PROHIBITED',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $work = $this->plugin->get($this->groupid);
        assertEquals(1, count($work));
        $data = json_decode($work[0]['data'], true);
        assertEquals('test@test.com', $data['email']);
        assertEquals('PROHIBITED', $data['postingStatus']);

        error_log(__METHOD__ . " end");
    }
}

