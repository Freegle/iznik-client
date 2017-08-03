<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/MembershipCollection.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/config/ModConfig.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class groupTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->exec("DELETE FROM groups WHERE nameshort = 'testgroup2';");
        $this->dbhm->exec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $this->dbhm->preExec("DELETE FROM users WHERE yahooid = '-testid1';");
        $this->dbhm->preExec("DELETE FROM users WHERE yahooid = '-testyahooid';");
        $this->dbhm->preExec("DELETE FROM users WHERE yahooUserId = '-testid1';");
        $this->dbhm->preExec("DELETE FROM users WHERE yahooUserId = '-testyahoouserid';");
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE users, users_emails FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE users_emails.backwards LIKE 'moctset%';");
        $dbhm->preExec("DELETE FROM users_emails WHERE users_emails.backwards LIKE 'moctset%';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testDefaults() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $this->dbhm->preExec("UPDATE groups SET settings = NULL WHERE id = ?;", [ $gid ]);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);
        $atts = $g->getPublic();

        assertEquals(1, $atts['settings']['duplicates']['check']);

        assertGreaterThan(0 ,$g->delete());

        assertGreaterThan(0, strpos($g->getGroupUnsubscribe(), 'unsub'));

        error_log(__METHOD__ . " end");
    }

    public function testBasic() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);
        $atts = $g->getPublic();
        assertEquals('testgroup', $atts['nameshort']);
        assertEquals($atts['id'], $g->getPrivate('id'));
        assertNull($g->getPrivate('invalidid'));

        # Test set members.
        $u = User::get($this->dbhr, $this->dbhm);
        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('TestConfig');
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($g->getId(), User::ROLE_MODERATOR);
        $mods = $g->getMods();
        assertTrue(in_array($this->uid, $mods));
        $this->user->setMembershipAtt($g->getId(), 'configid', $cid);
        $rc = $g->setMembers([
            [
                'uid' => $this->uid,
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);
        $membs = $g->getMembers();

        # Can't set owner status as not logged in as an owner.
        assertEquals(User::ROLE_MODERATOR, $membs[0]['role']);

        # Now try as an owner.
        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->addMembership($gid, User::ROLE_OWNER);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $rc = $g->setMembers([
            [
                'uid' => $this->uid,
                'yahooid' => 'testid',
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);
        $membs = $g->getMembers();
        assertEquals(User::ROLE_OWNER, $membs[0]['role']);

        $membs = $this->user->getMemberships();
        error_log("Got members" . var_export($membs, true));
        assertEquals(1, count($membs));
        assertEquals($cid, $membs[0]['configid']);

        assertGreaterThan(0 ,$g->delete());
        $c->delete();

        error_log(__METHOD__ . " end");
    }

    public function testMerge() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        assertNotNull($gid);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        # Create owner
        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $eid = $u->addEmail('test@test.com');
        error_log("Create owner $id with email $eid");
        $u->addMembership($gid, User::ROLE_OWNER, $eid);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        # Test merging by Yahoo ID.
        $rc = $g->setMembers([
            [
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ],
            [
                'yahooid' => '-testid1',
                'email' => 'test1@test.com'
            ],
            [
                'email' => 'test2@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);

        error_log("Before merge " . var_export($g->getMembers(), TRUE));

        $this->dbhm->preExec("UPDATE users_emails SET preferred = 1 WHERE email IN ('test1@test.com', 'test2@test.com')");

        $rc = $g->setMembers([
            [
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ],
            [
                'yahooid' => '-testid1',
                'email' => 'test2@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);

        $membs = $g->getMembers();
        error_log("Got " . count($membs) . " now");
        error_log(var_export($membs, TRUE));
        assertEquals('-testid1', $membs[0]['yahooid']);
        assertEquals('test1@test.com', $membs[0]['otheremails'][0]['email']);

        # Test merging by Yahoo User ID.
        error_log("Test merge by Yahoo User ID");
        $rc = $g->setMembers([
            [
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ],
            [
                'yahooUserId' => '-testid1',
                'email' => 'test11@test.com'
            ],
            [
                'email' => 'test12@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);

        $rc = $g->setMembers([
            [
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ],
            [
                'yahooUserId' => '-testid1',
                'email' => 'test12@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);

        $membs = $g->getMembers();
        error_log(var_export($membs, TRUE));
        assertEquals('-testid1', $membs[0]['yahooUserId']);
        assertEquals('test12@test.com', $membs[0]['otheremails'][0]['email']);

        # Test that the merge history is there.
        $this->waitBackground();
        error_log("Check merge history for {$membs[0]['userid']}");
        $u = User::get($this->dbhr, $this->dbhm, $membs[0]['userid']);
        $ctx = NULL;
        $atts = $u->getPublic(NULL, FALSE, TRUE, $ctx);
        error_log("Merge history " . var_export($atts, TRUE));
        assertEquals(1, count($atts['merges']));
        assertEquals($membs[0]['userid'], $atts['merges'][0]['from']);

        error_log(__METHOD__ . " end");
    }

    public function testSplit() {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->setPrivate('yahooid', '-testyahooid');
        $u->setPrivate('yahooUserId', '-testyahoouserid');
        assertNotNull($u->addEmail('test@test.com'));
        $u->split('test@test.com');
        assertNotNull($u->findByEmail('test@test.com'));
        assertNull($u->findByYahooId('-testyahooid'));
        assertNull($u->findByYahooUserId('-testyahoouserid'));

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => SQLHOST,
            'port_read' => SQLPORT_READ,
            'port_mod' => SQLPORT_MOD,
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        # Create duplicate group
        $g = Group::get($this->dbhr, $this->dbhm);
        $id = $g->create('testgroup', Group::GROUP_REUSE);
        assertEquals($id, $g->findByShortName('TeStGrOuP'));
        assertNotNull($id);
        $id2 = $g->create('testgroup', Group::GROUP_REUSE);
        assertNull($id2);

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('lastInsertId'))
            ->getMock();
        $mock->method('lastInsertId')->willThrowException(new Exception());
        $g->setDbhm($mock);
        $id2 = $g->create('testgroup2', Group::GROUP_REUSE);
        assertNull($id2);

        $g = Group::get($this->dbhr, $this->dbhm);
        $id2 = $g->findByShortName('zzzz');
        assertNull($id2);

        # Test errors in set members
        error_log("Set Members errors");
        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($id);

        # Error in preExec
        $g = Group::get($this->dbhr, $this->dbhm, $id);
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $g->setDbhm($mock);
        $rc = $g->setMembers([
            [
                'uid' => $this->uid,
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertNotEquals(0, $rc['ret']);

        # Error in exec
        $members = $g->getMembers();
        assertEquals(1, count($members));
        error_log("Members " . var_export($members, true));
        assertEquals(0, count($members[0]['otheremails']));

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('exec'))
            ->getMock();
        $mock->method('exec')->willThrowException(new Exception());
        $g->setDbhm($mock);

        $rc = $g->setMembers([
            [
                'uid' => $this->uid,
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertNotEquals(0, $rc['ret']);

        $members = $g->getMembers();
        assertEquals(1, count($members));
        assertEquals(0, count($members[0]['otheremails']));

        error_log(__METHOD__ . " end");
    }

    public function testVoucher() {
        error_log(__METHOD__ );

        $g = Group::get($this->dbhr, $this->dbhm);
        $id = $g->create('testgroup', Group::GROUP_REUSE);
        assertNotNull($id);
        assertNull($g->getPrivate('licensed'));
        assertNull($g->getPrivate('licenseduntil'));

        $voucher = $g->createVoucher();
        assertNotNull($voucher);
        assertFalse($g->redeemVoucher('wibble'));
        assertTrue($g->redeemVoucher($voucher));
        $g = Group::get($this->dbhr, $this->dbhm, $id);
        assertNotNull($g->getPrivate('licensed'));
        assertNotNull($g->getPrivate('licenseduntil'));

        error_log(__METHOD__ . " end");
    }

    public function testLegacy() {
        error_log(__METHOD__ );

        $sql = "SELECT id, legacyid FROM groups WHERE legacyid IS NOT NULL AND legacyid NOT IN (SELECT id FROM groups);";
        $groups = $this->dbhr->preQuery($sql);
        foreach ($groups as $group) {
            error_log("Get legacy {$group['legacyid']}");
            $g = Group::get($this->dbhr, $this->dbhm, $group['legacyid']);
            error_log("Returned id " . $g->getId());
            assertEquals($group['id'], $g->getId());
        }

        error_log(__METHOD__ . " end");
    }

    public function testNativeRoles()
    {
        error_log(__METHOD__);

        # Create a group with a mod and a member.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $modid = $u->create(NULL, NULL, 'Test User');
        $mod = User::get($this->dbhr, $this->dbhm, $modid);
        $mod->addEmail('test1@test.com');
        error_log("Created mod $modid");
        $ownid = $u->create(NULL, NULL, 'Test User');
        $own = User::get($this->dbhr, $this->dbhm, $ownid);
        $own->addEmail('test2@test.com');
        error_log("Created owner $ownid");

        $rc = $g->setMembers([
            [
                'yahooModeratorStatus' => 'MODERATOR',
                'email' => 'test1@test.com'
            ],
            [
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test2@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);

        # Should both be mods until we switch.
        self::assertEquals(User::ROLE_MODERATOR, $mod->getMembershipAtt($gid, 'role'));
        self::assertEquals(User::ROLE_MODERATOR, $own->getMembershipAtt($gid, 'role'));
        $g->setNativeRoles();
        $mod->clearMembershipCache();
        $own->clearMembershipCache();
        self::assertEquals(User::ROLE_MODERATOR, $mod->getMembershipAtt($gid, 'role'));
        self::assertEquals(User::ROLE_OWNER, $own->getMembershipAtt($gid, 'role'));

        error_log(__METHOD__ . " end");
    }
}

