<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class membershipsAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM users WHERE yahooid LIKE '-testid%';");
        $dbhm->preExec("DELETE FROM users WHERE yahooUserId LIKE '-testid%';");
        $dbhm->preExec("DELETE FROM users_emails WHERE backwards LIKE 'moctset%';");

        $this->group = Group::get($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_REUSE);
        $this->group->setPrivate('onyahoo', TRUE);
        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        assertNotNull($this->uid);
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->uid2 = $u->create(NULL, NULL, 'Test User');
        assertNotNull($this->uid);
        $this->user2 = User::get($this->dbhr, $this->dbhm, $this->uid2);
        $this->user2->addEmail('tes2t@test.com');
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($this->user->login('testpw'));
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testAdd() {
        error_log(__METHOD__);

        # Should be able to add (i.e. join) as a non-member or a member.
        $_SESSION['id'] = $this->uid2;

        $ret = $this->call('memberships', 'PUT', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'role' => 'Member'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'PUT', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'role' => 'Member',
            'email' => 'test2@test.com'
        ]);
        assertEquals(0, $ret['ret']);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        $ret = $this->call('memberships', 'PUT', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'role' => 'Member',
            'email' => 'test2@test.com'
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testJoinAndSee() {
        error_log(__METHOD__);

        # Check that if we join a group we can see messages on it immediately.
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $msg = str_replace('22 Aug 2015', '22 Aug 2035', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        error_log("Approved id $id");

        # Our role should be non-member.
        $this->user->removeMembership($this->groupid);

        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);

        self::assertEquals('Non-member', $ret['message']['myrole']);

        # Join
        $ret = $this->call('memberships', 'PUT', [
            'groupid' => $this->groupid,
            'userid' => $this->uid,
            'role' => 'Member'
        ]);
        assertEquals(0, $ret['ret']);

        # Our role should be member.
        $ret = $this->call('message', 'GET', [
            'id' => $id
        ]);

        # We have moderator role on our own message.
        self::assertEquals('Moderator', $ret['message']['myrole']);

        error_log(__METHOD__ . " end");
    }

    public function testRemove() {
        error_log(__METHOD__);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        $ret = $this->call('memberships', 'PUT', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'role' => 'Member',
            'email' => 'test2@test.com'
        ]);
        assertEquals(0, $ret['ret']);

        # Shouldn't be able to remove as non-member or member
        $this->user->removeMembership($this->groupid);
        $ret = $this->call('memberships', 'DELETE', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2
        ]);
        assertEquals(2, $ret['ret']);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MEMBER));
        $ret = $this->call('memberships', 'DELETE', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2
        ]);
        assertEquals(2, $ret['ret']);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_OWNER));
        $ret = $this->call('memberships', 'DELETE', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'dedup' => true
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testGet() {
        error_log(__METHOD__);

        # Shouldn't be able to get as non-member or member
        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid
        ]);
        error_log("Got memberships " . var_export($ret, TRUE));
        assertEquals(2, $ret['ret']);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MEMBER));
        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid
        ]);
        assertEquals(2, $ret['ret']);

        # Should be able to get the minimal set of membership info for unsubscribe.
        $ret = $this->call('memberships', 'GET', [
            'email' => 'test@test.com'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['memberships']));
        assertEquals($this->groupid, $ret['memberships'][0]['id']);
        $ret = $this->call('memberships', 'GET', [
            'email' => 'invalid@test.com'
        ]);
        assertEquals(3, $ret['ret']);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['members']));
        assertEquals($this->uid, $ret['members'][0]['userid']);

        # Sleep for background logging
        $this->waitBackground();

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'userid' => $this->uid,
            'logs' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($this->uid, $ret['member']['userid']);

        $log = $this->findLog('Group', 'Joined', $ret['member']['logs']);
        assertEquals($this->groupid, $log['group']['id']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'search' => 'test@'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['members']));
        assertEquals($this->uid, $ret['members'][0]['userid']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'search' => 'Test U'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['members']));
        assertEquals($this->uid, $ret['members'][0]['userid']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'search' => 'wibble'
        ]);
        error_log("wibble search " . var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['members']));

        error_log(__METHOD__ . " end");
    }

    public function testDemote() {
        error_log(__METHOD__);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        assertEquals(1, $this->user2->addMembership($this->groupid, User::ROLE_MEMBER));

        assertEquals(User::ROLE_MODERATOR, $this->user->getRoleForGroup($this->groupid));

        # Demote ourselves - should work
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid,
            'role' => 'Member'
        ]);
        assertEquals(0, $ret['ret']);

        assertEquals(User::ROLE_MEMBER, $this->user->getRoleForGroup($this->groupid));

        # Try again - should fail as we're not a mod now.
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'role' => 'Member'
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testSettings() {
        error_log(__METHOD__);

        # Shouldn't be able to set as a different member.
        $settings = [ 'test' => true ];

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'settings' => $settings
        ]);
        assertEquals(2, $ret['ret']);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MEMBER));

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid,
            'settings' => $settings
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'userid' => $this->uid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($settings, $ret['member']['settings']);

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid,
            'emailfrequency' => 8,
            'ourpostingstatus' => 'DEFAULT'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'userid' => $this->uid
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(8, $ret['member']['emailfrequency']);
        self::assertEquals('DEFAULT', $ret['member']['ourpostingstatus']);
        
        assertEquals(1, $this->user2->addMembership($this->groupid, User::ROLE_MEMBER));
        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2,
            'settings' => $settings
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'userid' => $this->uid2
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($settings, $ret['member']['settings']);

        # Set a config
        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('testconfig');
        assertNotNull($cid);
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'userid' => $this->uid,
            'settings' => [
                'configid' => $cid
            ]
        ]);
        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'userid' => $this->uid
        ]);

        assertEquals($cid, $ret['member']['settings']['configid']);

        error_log(__METHOD__ . " end");
    }

    public function testMembers() {
        error_log(__METHOD__);

        # Not logged in - shouldn't see members list
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(2, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Member - shouldn't see members list
        error_log("Login as " . $this->user->getId());
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(2, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Mod - should see members list
        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_OWNER));
        $members = [
            [
                'email' => 'test@test.com',
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'MODERATED',
                'yahooDeliveryType' => 'ANNOUNCEMENT',
                'yahooModeratorStatus' => 'MODERATOR',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ],
            [
                'email' => 'test2@test.com',
                'yahooUserId' => 2,
                'yahooid' => '-testid',
                'yahooPostingStatus' => 'UNMODERATED',
                'yahooDeliveryType' => 'SINGLE',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ],
            [
                'email' => 'test3@test.com',
                'yahooUserId' => 3,
                'yahooPostingStatus' => 'PROHIBITED',
                'yahooDeliveryType' => 'DIGEST',
                'name' => 'Test User',
                'yahooModeratorStatus' => 'OWNER',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ]
        ];

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => $members
        ]);
        assertEquals(0, $ret['ret']);
        
        # Fake background job.
        $g = Group::get($this->dbhr, $this->dbhm);
        User::clearCache();
        Group::clearCache();
        $g->processSetMembers();
        User::clearCache();
        Group::clearCache();
        $this->dbhr->clearCache();

        # Test user search - here as we want to check the Yahoo details too.
        $u = User::get($this->dbhr, $this->dbhm);
        $ctx = NULL;
        $users = $u->search('test@test.com', $ctx);
        error_log("Search returned " . var_export($users, TRUE));
        self::assertEquals(1, count($users));
        assertEquals('ANNOUNCEMENT', $users[0]['memberof'][0]['yahooDeliveryType']);

        $ret = $this->call('memberships', 'GET', []);

        assertEquals(3, count($ret['members']));
        assertEquals('test3@test.com', $ret['members'][0]['email']);
        assertEquals('Owner', $ret['members'][0]['role']);
        assertEquals('test2@test.com', $ret['members'][1]['email']);
        assertEquals('Member', $ret['members'][1]['role']);
        assertEquals('test@test.com', $ret['members'][2]['email']);
        assertEquals('Moderator', $ret['members'][2]['role']);
        $savemembs = $ret['members'];

        # Check filters
        error_log("Check filters");
        $ret = $this->call('memberships', 'GET', [ 'yahooPostingStatus' => 'PROHIBITED' ]);
        assertEquals(1, count($ret['members']));
        $ret = $this->call('memberships', 'GET', [ 'yahooDeliveryType' => 'DIGEST' ]);
        assertEquals(1, count($ret['members']));

        # Again - should get ignored
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => $members
        ]);
        assertEquals(0, $ret['ret']);

        # Test merge by yahooid and yahooUserId
        error_log("Test merge");
        $this->group = Group::get($this->dbhr, $this->dbhm, $this->groupid);
        $this->group->setPrivate('lastyahoomembersync', NULL);

        $members = [
            [
                'email' => 'test4@test.com',
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'MODERATED',
                'yahooDeliveryType' => 'ANNOUNCEMENT',
                'yahooModeratorStatus' => 'MODERATOR',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ],
            [
                'email' => 'test5@test.com',
                'yahooid' => '-testid',
                'yahooPostingStatus' => 'UNMODERATED',
                'yahooDeliveryType' => 'SINGLE',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ],
            [
                'email' => 'test@test.com',
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'MODERATED',
                'yahooDeliveryType' => 'ANNOUNCEMENT',
                'yahooModeratorStatus' => 'MODERATOR',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2016 10:45:58 +0000')
            ]
        ];

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => $members,
            'dup' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', []);
        error_log("Saved " .var_export( $savemembs, TRUE));
        error_log("Returned " . var_export($ret, TRUE));

        assertEquals(3, count($ret['members']));

        error_log(__METHOD__ . " end");
    }

    public function testPendingMembers() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));
        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_OWNER));

        $ret = $this->call('memberships', 'GET', [
            'collection' => MembershipCollection::APPROVED
        ]);
        error_log("Returned " . var_export($ret, TRUE));

        assertEquals(1, count($ret['members']));

        error_log("Pending members on {$this->groupid}");

        $members = [
            [
                'email' => 'test1@test.com'
            ],
            [
                'email' => 'test2@test.com'
            ]
        ];

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => $members,
            'collection' => MembershipCollection::PENDING
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'collection' => MembershipCollection::PENDING
        ]);
        error_log("Returned " . var_export($ret, TRUE));

        assertEquals(2, count($ret['members']));

        error_log(__METHOD__ . " end");
    }

    public function testReject() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        assertNotNull($uid);
        assertTrue($u->addMembership($this->groupid, User::ROLE_MEMBER, NULL, MembershipCollection::PENDING));
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));
        $this->dbhm->preExec("UPDATE memberships_yahoo SET yahooapprove = 'test@test.com', yahooreject = 'test@test.com' WHERE membershipid = (SELECT id FROM memberships WHERE userid = $uid);");
        $this->dbhm->preExec("UPDATE users SET yahooUserId = 1 WHERE id = $uid;");
        User::clearCache($uid);

        $u->addEmail('test2@test.com');
        assertEquals(1, $this->user->addMembership($this->groupid));

        # Shouldn't be able to do this as a non-member.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Reject',
            'subject' => "Test",
            'body' => "Test"
        ]);
        assertEquals(2, $ret['ret']);
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));

        # Shouldn't be able to do this as a member
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Reject',
            'body' => "Test",
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        # Can't reject ourselves as we're not pending.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $this->uid,
            'groupid' => $this->groupid,
            'action' => 'Reject',
            'subject' => "Test",
            'body' => "Test",
            'dup' => 11
        ]);
        assertEquals(3, $ret['ret']);

        # Should be able to mail.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Leave Member',
            'subject' => "Test",
            'body' => "Test",
            'dup' => 2
        ]);
        assertEquals(0, $ret['ret']);

        # Should work as a moderator, and will not be pending any more.
        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('Test');
        $c->setPrivate('ccrejectto', 'Me');

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s = new StdMessage($this->dbhr, $this->dbhm, $sid);

        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Reject',
            'subject' => "Test",
            'body' => "Test",
            'stdmsgid' => $sid,
            'dup' => 2
        ]);
        assertEquals(0, $ret['ret']);
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(0, count($members));
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::APPROVED, [ $this->groupid ]);
        assertEquals(1, count($members));

        # Plugin work should exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['plugin']));
        assertEquals($this->groupid, $ret['plugin'][0]['groupid']);
        assertEquals('{"type":"RejectPendingMember","id":"1","email":"test2@test.com"}', $ret['plugin'][0]['data']);
        $pid = $ret['plugin'][0]['id'];

        $ret = $this->call('plugin', 'DELETE', [
            'id' => $pid
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, count($ret['plugin']));

        error_log(__METHOD__ . " end");
    }

    public function testDelete() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        assertNotNull($uid);
        assertTrue($u->addMembership($this->groupid, User::ROLE_MEMBER, NULL, MembershipCollection::APPROVED));

        # Shouldn't be able to do this as a non-member.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Delete',
            'subject' => "Test",
            'body' => "Test"
        ]);
        assertEquals(2, $ret['ret']);

        # Shouldn't be able to do this as a member
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Delete',
            'body' => "Test",
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        assertTrue($this->user->addMembership($this->groupid, User::ROLE_MODERATOR, NULL, MembershipCollection::APPROVED));

        # Should work as a moderator, and will not be pending any more.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Delete',
            'dup' => 2
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testUnsubscribe() {
        error_log(__METHOD__);

        assertTrue($this->user->addMembership($this->groupid, User::ROLE_MEMBER, NULL, MembershipCollection::APPROVED));

        # For invalid user
        $ret = $this->call('memberships', 'DELETE', [
            'email' => 'invalid@test.com',
            'groupid' => $this->groupid
        ]);
        assertEquals(3, $ret['ret']);

        # For invalid group
        $ret = $this->call('memberships', 'DELETE', [
            'email' => 'test@test.com',
            'groupid' => $this->groupid + 1
        ]);
        assertEquals(4, $ret['ret']);

        # For mod
        assertTrue($this->user->addMembership($this->groupid, User::ROLE_MODERATOR, NULL, MembershipCollection::APPROVED));
        $ret = $this->call('memberships', 'DELETE', [
            'email' => 'test@test.com',
            'groupid' => $this->groupid
        ]);
        assertEquals(4, $ret['ret']);

        # Success
        assertTrue($this->user->addMembership($this->groupid, User::ROLE_MEMBER, NULL, MembershipCollection::APPROVED));
        $ret = $this->call('memberships', 'DELETE', [
            'email' => 'test@test.com',
            'groupid' => $this->groupid
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testApprove() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $eid = $u->addEmail('test2@test.com');
        assertNotNull($uid);
        assertTrue($u->addMembership($this->groupid, User::ROLE_MEMBER, $eid, MembershipCollection::PENDING));
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));
        $this->dbhm->preExec("UPDATE memberships_yahoo SET yahooapprove = 'test@test.com', yahooreject = 'test@test.com' WHERE  membershipid = (SELECT id FROM memberships WHERE userid = $uid);");

        $this->dbhm->preExec("UPDATE users SET yahooUserId = 1 WHERE id = $uid;");
        User::clearCache($uid);
        $u->addEmail('test2@test.com');
        assertEquals(1, $this->user->addMembership($this->groupid));

        # Shouldn't be able to do this as a non-member.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Approve',
            'subject' => "Test",
            'body' => "Test"
        ]);
        assertEquals(2, $ret['ret']);
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));

        # Shouldn't be able to do this as a member
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Approve',
            'body' => "Test",
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));

        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

        # Can't approve ourselves as we're not pending.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $this->uid,
            'groupid' => $this->groupid,
            'action' => 'Approve',
            'subject' => "Test",
            'body' => "Test",
            'dup' => 11
        ]);
        assertEquals(3, $ret['ret']);

        # Should work as a moderator, and will not be pending any more.
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Approve',
            'subject' => "Test",
            'body' => "Test",
            'groupid' => $this->groupid,
            'dup' => 2
        ]);
        assertEquals(0, $ret['ret']);
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(0, count($members));
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::APPROVED, [ $this->groupid ]);
        assertEquals(2, count($members));

        # Plugin work should exist
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['plugin']));
        assertEquals($this->groupid, $ret['plugin'][0]['groupid']);
        assertEquals('{"type":"ApprovePendingMember","id":"1","email":"test2@test.com"}', $ret['plugin'][0]['data']);
        $pid = $ret['plugin'][0]['id'];

        $ret = $this->call('plugin', 'DELETE', [
            'id' => $pid
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('plugin', 'GET', []);
        assertEquals(0, count($ret['plugin']));

        error_log(__METHOD__ . " end");
    }

    public function testHold() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        assertNotNull($uid);
        assertTrue($u->addMembership($this->groupid, User::ROLE_MEMBER, NULL, MembershipCollection::PENDING));
        $ctx = NULL;
        $members = $this->group->getMembers(10, NULL, $ctx, NULL, MembershipCollection::PENDING, [ $this->groupid ]);
        assertEquals(1, count($members));
        assertEquals(1, $this->user->addMembership($this->groupid));

        # Shouldn't be able to hold logged out
        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Hold'
        ]);
        assertEquals(2, $ret['ret']);

        # Nor as a member
        assertTrue($this->user->login('testpw'));

        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Hold',
            'dup' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Promote to owner - should be able to hold it.
        $this->user->setRole(User::ROLE_OWNER, $this->groupid);

        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Hold',
            'duplicate' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'collection' => 'Wibble'
        ]);
        assertEquals(3, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'collection' => 'Pending'
        ]);
        assertEquals($this->uid, $ret['member']['heldby']['id']);

        $ret = $this->call('memberships', 'POST', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'action' => 'Release',
            'duplicate' => 2
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'userid' => $uid,
            'groupid' => $this->groupid,
            'collection' => 'Pending'
        ]);
        assertFalse(pres('heldby', $ret['member']));

        error_log(__METHOD__ . " end");
    }

    public function testLarge() {
        error_log(__METHOD__);

        $size = 1000;

        assertTrue($this->user->login('testpw'));
        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_OWNER));

        $members = [
            [
                'email' => 'test@test.com',
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'MODERATED',
                'yahooDeliveryType' => 'ANNOUNCEMENT',
                'yahooModeratorStatus' => 'MODERATOR',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ]
        ];

        for ($i = 0; $i < $size; $i++) {
            if ($i % 1000 == 0) {
                error_log("...$i");
            }
            $members[] = [
                'email' => "test$i@test.com",
                'yahooUserId' => "-$i",
                'yahooPostingStatus' => 'UNMODERATED',
                'yahooDeliveryType' => 'SINGLE',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ];
        };

        error_log("Do PATCH");
        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => $members
        ]);
        assertEquals(0, $ret['ret']);
        error_log("Done PATCH");

        $g = Group::get($this->dbhr, $this->dbhm);
        $g->processSetMembers($this->groupid);
        User::clearCache();
        
        $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = ?;";
        $counts = $this->dbhr->preQuery($sql, [ $this->groupid ]);
        assertEquals($size + 1, $counts[0]['count']);

        error_log(__METHOD__ . " end");
    }

    public function testExportYahoo() {
        error_log(__METHOD__);

        assertTrue($this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($this->user->login('testpw'));

        # Export with no entry in the table.
        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'action' => 'exportyahoo'
        ]);
        assertEquals(0, $ret['ret']);
        assertFalse(pres('members', $ret));

        $members = [
            [
                'email' => 'test@test.com',
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'MODERATED',
                'yahooDeliveryType' => 'ANNOUNCEMENT',
                'yahooModeratorStatus' => 'MODERATOR',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ]
        ];

        for ($i = 0; $i < 10; $i++) {
            $members[] = [
                'email' => "test$i@test.com",
                'yahooUserId' => "-$i",
                'yahooPostingStatus' => 'UNMODERATED',
                'yahooDeliveryType' => 'SINGLE',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ];
        };

        $ret = $this->call('memberships', 'PATCH', [
            'groupid' => $this->groupid,
            'members' => $members
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('memberships', 'GET', [
            'groupid' => $this->groupid,
            'action' => 'exportyahoo'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(11, count($ret['members']));

        error_log(__METHOD__ . " end");
    }

    public function testFilter() {
        error_log(__METHOD__);

        assertEquals(1, $this->user->addMembership($this->groupid, User::ROLE_MODERATOR));
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('memberships', 'GET', [
            'id' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->addMembership($this->groupid);

        $ret = $this->call('memberships', 'GET', [
            'id' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(2, count($ret['members']));

        $ret = $this->call('memberships', 'GET', [
            'id' => $this->groupid,
            'filter' => Group::FILTER_NONE,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(2, count($ret['members']));

        $ret = $this->call('memberships', 'GET', [
            'id' => $this->groupid,
            'filter' => Group::FILTER_WITHCOMMENTS,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['members']));

        $ret = $this->call('memberships', 'GET', [
            'id' => $this->groupid,
            'filter' => Group::FILTER_WITHCOMMENTS,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['members']));

        $u->addComment($this->groupid, 'Test comment');

        $ret = $this->call('memberships', 'GET', [
            'id' => $this->groupid,
            'filter' => Group::FILTER_WITHCOMMENTS,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['members']));

        error_log(__METHOD__ . " end");
    }
}

