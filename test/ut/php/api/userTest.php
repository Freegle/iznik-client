<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class userAPITest extends IznikAPITestCase {
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
        $dbhm->preExec("DELETE FROM users WHERE yahooUserId = '1';");

        # Create a moderator and log in as them
        $this->group = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        assertEquals(1, $this->user->addMembership($this->groupid));
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $this->uid2 = $u->create(NULL, NULL, 'Test User');
        $this->user2 = User::get($this->dbhr, $this->dbhm, $this->uid2);
        $this->user2->addEmail('test2@test.com');
        assertEquals(1, $this->user2->addMembership($this->groupid));
        assertGreaterThan(0, $this->user2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        assertTrue($this->user->login('testpw'));

        $this->plugin = new Plugin($this->dbhr, $this->dbhm);
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testRegister() {
        error_log(__METHOD__);
        
        $email = 'test3@test.com';

        # Invalid
        $ret = $this->call('user', 'PUT', [
            'password' => 'wibble'
        ]);
        assertEquals(1, $ret['ret']);
        
        # Register successfully
        error_log("Register expect success");
        $ret = $this->call('user', 'PUT', [
            'email' => $email,
            'password' => 'wibble'
        ]);
        error_log("Expect success returned " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        assertNotNull($id);

        $ret = $this->call('user', 'GET', [
            'id' => $id,
            'info' => TRUE
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals($email, $ret['user']['emails'][0]['email']);
        assertTrue(array_key_exists('replies', $ret['user']['info']));

        # Register with email already taken and wrong password
        $ret = $this->call('user', 'PUT', [
            'email' => $email,
            'password' => 'wibble2'
        ]);
        assertEquals(2, $ret['ret']);

        # Register with same email and pass
        $ret = $this->call('user', 'PUT', [
            'email' => $email,
            'password' => 'wibble'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['id']);

        error_log(__METHOD__ . " end");
    }
    
    public function testDeliveryType() {
        error_log(__METHOD__);

        # Shouldn't be able to do this as a member
        $ret = $this->call('user', 'PATCH', [
            'groupid' => $this->groupid,
            'yahooDeliveryType' => 'DIGEST',
            'email' => 'test@test.com'
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        $ret = $this->call('user', 'PATCH', [
            'groupid' => $this->groupid,
            'suspectcount' => 0,
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

        $ret = $this->call('user', 'PATCH', [
            'yahooUserId' => 1,
            'groupid' => $this->groupid,
            'yahooPostingStatus' => 'PROHIBITED'
        ]);
        assertEquals(2, $ret['ret']);

        $this->dbhm->preExec("UPDATE users SET yahooUserId = 1 WHERE id = ?;", [ $this->uid ]);
        User::clearCache($this->uid);

        # Shouldn't be able to do this as a member
        $ret = $this->call('user', 'PATCH', [
            'yahooUserId' => 1,
            'groupid' => $this->groupid,
            'yahooPostingStatus' => 'PROHIBITED',
            'duplicate' => 0
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        $ret = $this->call('user', 'PATCH', [
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

    public function testPassword() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create("Test", "User", "Test User");
        $u->addEmail('test2@test.com');

        $ret = $this->call('user', 'PATCH', [
            'id' => $this->uid,
            'password' => 'testtest'
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);

        $ret = $this->call('user', 'PATCH', [
            'id' => $this->uid,
            'password' => 'testtest'
        ]);
        assertEquals(0, $ret['ret']);

        assertFalse($u->login('testbad'));
        assertFalse($u->login('testtest'));

        error_log(__METHOD__ . " end");
    }

    public function testMail() {
        error_log(__METHOD__);

        # Mails won't go through as there's no email address, but we're just testing the API.
        #
        # Shouldn't be able to do this as a non-member.
        $ret = $this->call('user', 'POST', [
            'action' => 'Reply',
            'subject' => "Test",
            'body' => "Test"
        ]);
        assertEquals(2, $ret['ret']);

        # Shouldn't be able to do this as a member
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('user', 'POST', [
            'subject' => "Test",
            'body' => "Test",
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        $ret = $this->call('user', 'POST', [
            'action' => 'Mail',
            'subject' => "Test",
            'body' => "Test",
            'groupid' => $this->groupid,
            'dup' => 2
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testLog() {
        error_log(__METHOD__);

        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        # Sleep for background logging
        $this->waitBackground();

        $ret = $this->call('user', 'GET', [
            'id' => $this->uid,
            'logs' => TRUE
        ]);

        # Can't see logs when another user who is not not a mod on the group
        error_log("Check can't see {$this->uid} as other member {$this->uid2}");
        $ret = $this->call('session', 'POST', [
            'email' => 'test2@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('user', 'GET', [
            'id' => $this->uid,
            'logs' => TRUE
        ]);

        $log = $this->findLog('Group', 'Joined', $ret['user']['logs']);
        assertNull($log);

        # Promote.
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        # Sleep for background logging
        $this->waitBackground();

        $ret = $this->call('user', 'GET', [
            'id' => $this->uid,
            'logs' => TRUE
        ]);
        $log = $this->findLog('Group', 'Joined', $ret['user']['logs']);
        assertEquals($this->groupid, $log['group']['id']);

        # Can also see as ourselves.
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('user', 'GET', [
            'id' => $this->uid,
            'logs' => TRUE
        ]);
        $log = $this->findLog('Group', 'Joined', $ret['user']['logs']);
        assertEquals($this->groupid, $log['group']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testDelete() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');

        $ret = $this->call('user', 'DELETE', [
            'id' => $uid
        ]);
        assertEquals(2, $ret['ret']);

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);

        $ret = $this->call('user', 'DELETE', [
            'id' => $uid
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testSupportSearch() {
        error_log(__METHOD__);

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);
        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MEMBER));

        # Search across all groups.
        $ret = $this->call('user', 'GET', [
            'search' => 'test@test'
        ]);
        error_log("Search returned " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['users']));
        assertEquals($this->uid, $ret['users'][0]['id']);

        # Test that a mod can't see stuff
        $this->user->setPrivate('systemrole', User::SYSTEMROLE_MODERATOR);
        assertEquals(1, $this->user->removeMembership($this->groupid));

        # Search across all groups.
        $ret = $this->call('user', 'GET', [
            'search' => 'tes2t@test.com'
        ]);
        error_log("Should fail " . var_export($ret, TRUE));
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

