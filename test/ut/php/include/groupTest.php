<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/MembershipCollection.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/config/ModConfig.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class groupTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->exec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $this->dbhm->preExec("DELETE FROM users WHERE id IN (SELECT userid FROM users_emails WHERE email LIKE '%test.com');", []);
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $g = new Group($this->dbhr, $this->dbhm, $gid);
        $atts = $g->getPublic();
        assertEquals('testgroup', $atts['nameshort']);
        assertEquals($atts['id'], $g->getPrivate('id'));
        assertNull($g->getPrivate('invalidid'));

        # Test set members.
        $u = new User($this->dbhr, $this->dbhm);
        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('TestConfig');
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($g->getId(), User::ROLE_MODERATOR);
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
        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->addMembership($gid, User::ROLE_OWNER);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $rc = $g->setMembers([
            [
                'uid' => $this->uid,
                'yahooModeratorStatus' => 'OWNER',
                'email' => 'test@test.com'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);
        $membs = $g->getMembers();
        assertEquals(User::ROLE_OWNER, $membs[0]['role']);

        $membs = $this->user->getMemberships();
        error_log("Got members" . var_export($membs, true));
        assertEquals($cid, $membs[0]['configid']);

        assertGreaterThan(0 ,$g->delete());
        $c->delete();

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        # Create duplicate group
        $g = new Group($this->dbhr, $this->dbhm);
        $id = $g->create('testgroup', Group::GROUP_REUSE);
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

        $g = new Group($this->dbhr, $this->dbhm);
        $id2 = $g->findByShortName('zzzz');
        assertNull($id2);

        # Test errors in set members
        error_log("Set Members errors");
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($id);

        # Error in preExec
        $g = new Group($this->dbhr, $this->dbhm, $id);
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

        # Error in commit
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('commit'))
            ->getMock();
        $mock->method('commit')->willReturn(false);
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
        assertEquals('test@test.com', $members[0]['otheremails'][0]['email']);

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
        assertEquals('test@test.com', $members[0]['otheremails'][0]['email']);

        error_log(__METHOD__ . " end");
    }
}

