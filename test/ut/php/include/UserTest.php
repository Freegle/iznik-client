<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/message/Message.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class userTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_emails WHERE email IN ('test@test.com', 'test2@test.com'));");
        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_logins WHERE uid IN ('testid', '1234'));");
        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM users WHERE firstname = 'Test' AND lastname = 'User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup1';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup2';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup3';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $atts = $u->getPublic();
        assertEquals('Test', $atts['firstname']);
        assertEquals('User', $atts['lastname']);
        assertNull($atts['fullname']);
        assertEquals('Test User', $u->getName());
        assertEquals($id, $u->getPrivate('id'));
        assertNull($u->getPrivate('invalidid'));

        assertGreaterThan(0, $u->delete());

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $atts = $u->getPublic();
        assertNull($atts['firstname']);
        assertNull($atts['lastname']);
        assertEquals('Test User', $atts['fullname']);
        assertEquals('Test User', $u->getName());
        assertEquals($id, $u->getPrivate('id'));
        assertGreaterThan(0, $u->delete());

        error_log(__METHOD__ . " end");
    }

    public function testEmails() {
        error_log(__METHOD__);

        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertEquals(0, count($u->getEmails()));

        # Add an email - should work.
        assertGreaterThan(0, $u->addEmail('test@test.com'));

        # Check it's there
        $emails = $u->getEmails();
        assertEquals(1, count($emails));
        assertEquals('test@test.com', $emails[0]['email']);

        # Add it again - should work
        assertGreaterThan(0, $u->addEmail('test@test.com'));

        # Add a second
        assertGreaterThan(0, $u->addEmail('test2@test.com', 0));
        $emails = $u->getEmails();
        assertEquals(2, count($emails));
        assertEquals(0, $emails[1]['preferred']);
        assertEquals($id, $u->findByEmail('test2@test.com'));
        assertGreaterThan(0, $u->removeEmail('test2@test.com'));
        assertNull($u->findByEmail('test2@test.com'));

        assertEquals($id, $u->findByEmail('test@test.com'));
        assertNull($u->findByEmail('testinvalid@test.com'));

        # Add them as memberships and check we get the right ones.
        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $emailid1 = $u->getIdForEmail('test@test.com');
        $emailid2 = $u->getIdForEmail('test2@test.com');
        $u->addMembership($group1, User::ROLE_MEMBER, $emailid1);
        assertEquals($emailid1, $u->getEmailForGroup($group1));
        $u->addMembership($group1, User::ROLE_MEMBER, $emailid2);
        $u->addMembership($group1, User::ROLE_MEMBER, $emailid2);
        assertEquals($emailid2, $u->getEmailForGroup($group1));
        assertNull($u->getIdForEmail('wibble@test.com'));
        assertNull($u->getEmailForGroup(-1));

        error_log(__METHOD__ . " end");
    }

    public function testLogins() {
        error_log(__METHOD__);

        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertEquals(0, count($u->getEmails()));

        # Add a login - should work.
        assertGreaterThan(0, $u->addLogin(User::LOGIN_YAHOO, 'testid'));

        # Check it's there
        $logins = $u->getLogins();
        assertEquals(1, count($logins));
        assertEquals('testid', $logins[0]['uid']);

        # Add it again - should work
        assertEquals(1, $u->addLogin(User::LOGIN_YAHOO, 'testid'));

        # Add a second
        assertGreaterThan(0, $u->addLogin(User::LOGIN_FACEBOOK, '1234'));
        $logins = $u->getLogins();
        assertEquals(2, count($logins));
        assertEquals($id, $u->findByLogin(User::LOGIN_FACEBOOK, '1234'));
        assertNull($u->findByLogin(User::LOGIN_YAHOO, '1234'));
        assertNull($u->findByLogin(User::LOGIN_FACEBOOK, 'testid'));
        assertGreaterThan(0, $u->removeLogin(User::LOGIN_FACEBOOK, '1234'));
        assertNull($u->findByLogin(User::LOGIN_FACEBOOK, '1234'));

        assertEquals($id, $u->findByLogin(User::LOGIN_YAHOO, 'testid'));
        assertNull($u->findByLogin(User::LOGIN_YAHOO, 'testinvalid'));

        # Test native
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        assertFalse($u->login('testpwbad'));

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $u = new User($this->dbhr, $this->dbhm);
        $u->setDbhm($mock);
        $id = $u->create(NULL, NULL, 'Test User');
        assertNull($id);

        error_log(__METHOD__ . " end");
    }

    public function testMemberships() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $this->dbhm->preExec("UPDATE users SET yahooUserId = 1 WHERE id = $id;");
        $u = new User($this->dbhr, $this->dbhm, $id);
        assertGreaterThan(0, $u->addEmail('test@test.com'));
        assertEquals($u->getRole($group1), User::ROLE_NONMEMBER);
        assertFalse($u->isModOrOwner($group1));

        $u->addMembership($group1, User::ROLE_MEMBER);
        assertEquals($u->getRole($group1), User::ROLE_MEMBER);
        assertFalse($u->isModOrOwner($group1));
        $u->setGroupSettings($group1, [
            'testsetting' => 'test'
        ]);
        assertEquals('test', $u->getGroupSettings($group1)['testsetting']);

        $u->addMembership($group1, User::ROLE_OWNER);
        assertEquals($u->getRole($group1), User::ROLE_OWNER);
        assertTrue($u->isModOrOwner($group1));
        assertTrue(array_key_exists('work', $u->getMemberships()[0]));
        $modships = $u->getModeratorships();
        assertEquals(1, count($modships));

        $u->setRole(User::ROLE_MODERATOR, $group1);
        assertEquals($u->getRole($group1), User::ROLE_MODERATOR);
        assertTrue($u->isModOrOwner($group1));
        assertTrue(array_key_exists('work', $u->getMemberships()[0]));
        $modships = $u->getModeratorships();
        assertEquals(1, count($modships));

        $u->addMembership($group2);
        $membs = $u->getMemberships();
        assertEquals(2, count($membs));

        $u->removeMembership($group1);
        assertEquals($u->getRole($group1), User::ROLE_NONMEMBER);
        $membs = $u->getMemberships();
        assertEquals(1, count($membs));
        assertEquals($group2, $membs[0]['id']);

        // Support and admin users have a mod role on the group even if not a member
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $msg = file_get_contents('msgs/basic');
        $msg = str_replace('Basic test', 'OFFER: Test item (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'testgroup1@yahoogroups.com', $msg);
        $mid = $m->save();
        $m = new Message($this->dbhr, $this->dbhm, $mid);

        $u->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);
        assertEquals($u->getRole($group1), User::ROLE_MODERATOR);
        assertEquals(User::ROLE_MODERATOR, $m->getRoleForMessage());
        $u->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);
        assertEquals($u->getRole($group1), User::ROLE_OWNER);
        assertEquals(User::ROLE_OWNER, $m->getRoleForMessage());

        # Ban ourselves; can't rejoin
        $u->removeMembership($group2, TRUE);
        $membs = $u->getMemberships();
        assertEquals(0, count($membs));
        assertFalse($u->addMembership($group2));

        $g = new Group($this->dbhr, $this->dbhm, $group1);
        $g->delete();
        $g = new Group($this->dbhr, $this->dbhm, $group2);
        $g->delete();

        $membs = $u->getMemberships();
        assertEquals(0, count($membs));

        error_log(__METHOD__ . " end");
    }

    public function testMerge() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);
        $group3 = $g->create('testgroup3', Group::GROUP_REUSE);

        $u = new User($this->dbhr, $this->dbhm);
        $id1 = $u->create(NULL, NULL, 'Test User');
        $id2 = $u->create(NULL, NULL, 'Test User');
        $u1 = new User($this->dbhr, $this->dbhm, $id1);
        $u2 = new User($this->dbhr, $this->dbhm, $id2);
        assertGreaterThan(0, $u1->addEmail('test1@test.com'));
        assertGreaterThan(0, $u1->addEmail('test2@test.com', 0));

        # Set up various memberships
        $u1->addMembership($group1, User::ROLE_MODERATOR);
        $u2->addMembership($group1, User::ROLE_MEMBER);
        $u2->addMembership($group2, User::ROLE_OWNER);
        $u1->addMembership($group3, User::ROLE_MEMBER);
        $u2->addMembership($group3, User::ROLE_MODERATOR);

        # Merge u2 into u1
        assertTrue($u1->merge($id1, $id2));

        # Pick up new settings.
        $u1 = new User($this->dbhr, $this->dbhm, $id1);
        $u2 = new User($this->dbhr, $this->dbhm, $id2);

        # u2 doesn't exist
        assertNull($u2->getId());

        # Now u1 is a member of all three
        $membs = $u1->getMemberships();
        assertEquals(3, count($membs));
        assertEquals($group1, $membs[0]['id']);
        assertEquals($group2, $membs[1]['id']);
        assertEquals($group3, $membs[2]['id']);

        # The merge should have preserved the highest setting.
        assertEquals(User::ROLE_MODERATOR, $membs[0]['role']);
        assertEquals(User::ROLE_OWNER, $membs[1]['role']);
        assertEquals(User::ROLE_MODERATOR, $membs[2]['role']);

        $emails = $u1->getEmails();
        error_log("Emails " . var_export($emails, true));
        assertEquals(2, count($emails));
        assertEquals('test1@test.com', $emails[0]['email']);
        assertEquals(1, $emails[0]['preferred']);
        assertEquals('test2@test.com', $emails[1]['email']);
        assertEquals(0, $emails[1]['preferred']);

        $atts = $u1->getPublic(NULL, FALSE, TRUE);
        error_log("ID is " . $u1->getId() . " public " . var_export($atts, true));
        $log = $this->findLog('User', 'Merged', $atts['logs']);
        assertEquals($id1, $log['user']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testMergeError() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);
        $group3 = $g->create('testgroup3', Group::GROUP_REUSE);

        $u = new User($this->dbhr, $this->dbhm);
        $id1 = $u->create(NULL, NULL, 'Test User');
        $id2 = $u->create(NULL, NULL, 'Test User');
        $u1 = new User($this->dbhr, $this->dbhm, $id1);
        $u2 = new User($this->dbhr, $this->dbhm, $id2);
        assertGreaterThan(0, $u1->addEmail('test1@test.com'));
        assertGreaterThan(0, $u1->addEmail('test2@test.com', 1));

        # Set up various memberships
        $u1->addMembership($group1, User::ROLE_MODERATOR);
        $u2->addMembership($group1, User::ROLE_MEMBER);
        $u2->addMembership($group2, User::ROLE_OWNER);
        $u1->addMembership($group3, User::ROLE_MEMBER);
        $u2->addMembership($group3, User::ROLE_MODERATOR);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ), TRUE))
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $u1->setDbhm($mock);

        # Merge u2 into u1
        assertFalse($u1->merge($id1, $id2));

        # Pick up new settings.
        $u1 = new User($this->dbhr, $this->dbhm, $id1);
        $u2 = new User($this->dbhr, $this->dbhm, $id2);

        # Both exist
        assertNotNull($u1->getId());
        assertNotNull($u2->getId());

        error_log(__METHOD__ . " end");
    }

    public function testRoleMax() {

        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);

        assertEquals(User::ROLE_OWNER, $u->roleMax(User::ROLE_MEMBER, User::ROLE_OWNER));
        assertEquals(User::ROLE_OWNER, $u->roleMax(User::ROLE_OWNER, User::ROLE_MODERATOR));

        assertEquals(User::ROLE_MODERATOR, $u->roleMax(User::ROLE_MEMBER, User::ROLE_MODERATOR));
        assertEquals(User::ROLE_MODERATOR, $u->roleMax(User::ROLE_MODERATOR, User::ROLE_NONMEMBER));

        assertEquals(User::ROLE_MEMBER, $u->roleMax(User::ROLE_MEMBER, User::ROLE_MEMBER));
        assertEquals(User::ROLE_MEMBER, $u->roleMax(User::ROLE_MEMBER, User::ROLE_NONMEMBER));

        assertEquals(User::ROLE_NONMEMBER, $u->roleMax(User::ROLE_NONMEMBER, User::ROLE_NONMEMBER));

        error_log(__METHOD__ . " end");
    }

    public function testRoleMin() {

        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);

        assertEquals(User::ROLE_MEMBER, $u->roleMin(User::ROLE_MEMBER, User::ROLE_OWNER));
        assertEquals(User::ROLE_MODERATOR, $u->roleMin(User::ROLE_OWNER, User::ROLE_MODERATOR));

        assertEquals(User::ROLE_MEMBER, $u->roleMin(User::ROLE_MEMBER, User::ROLE_MODERATOR));
        assertEquals(User::ROLE_NONMEMBER, $u->roleMin(User::ROLE_MODERATOR, User::ROLE_NONMEMBER));

        assertEquals(User::ROLE_MEMBER, $u->roleMin(User::ROLE_MEMBER, User::ROLE_MEMBER));
        assertEquals(User::ROLE_NONMEMBER, $u->roleMin(User::ROLE_MEMBER, User::ROLE_NONMEMBER));

        assertEquals(User::ROLE_NONMEMBER, $u->roleMax(User::ROLE_NONMEMBER, User::ROLE_NONMEMBER));

        error_log(__METHOD__ . " end");
    }

    public function testMail() {
        error_log(__METHOD__ );

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $g = new Group($this->dbhr, $this->dbhm);
        $group = $g->create('testgroup1', Group::GROUP_REUSE);

        # Suppress mails.
        $u = $this->getMockBuilder('User')
        ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
        ->setMethods(array('mailer'))
        ->getMock();
        $u->method('mailer')->willReturn(false);
        assertGreaterThan(0, $u->addEmail('test@test.com'));
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $c = new ModConfig($this->dbhr, $this->dbhm);
        $cid = $c->create('Test');
        $c->setPrivate('ccfollmembto', 'Specifc');
        $c->setPrivate('ccfollmembaddr', 'test@test.com');

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s->setPrivate('action', 'Reject Member');

        $u->mail($group, "test", "test", $sid);

        $s->delete();

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s->setPrivate('action', 'Leave Approved Member');

        $u->mail($group, "test", "test", $sid);

        $s->delete();
        $c->delete();

        error_log(__METHOD__ . " end");
    }

    public function testComments() {
        error_log(__METHOD__);

        $u1 = new User($this->dbhr, $this->dbhm);
        $id1 = $u1->create('Test', 'User', NULL);
        $u2 = new User($this->dbhr, $this->dbhm);
        $id2 = $u2->create('Test', 'User', NULL);
        assertGreaterThan(0, $u1->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u1->login('testpw'));

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);

        # Try to add a comment when not a mod.
        assertNull($u2->addComment($gid, "Test comment"));
        $u1->addMembership($gid);
        assertNull($u2->addComment($gid, "Test comment"));
        $u1->setRole(User::ROLE_MODERATOR, $gid);
        $cid = $u2->addComment($gid, "Test comment");
        assertNotNull($cid);
        $atts = $u2->getPublic();
        assertEquals(1, count($atts['comments']));
        assertEquals($cid, $atts['comments'][0]['id']);
        assertEquals("Test comment", $atts['comments'][0]['user1']);
        assertEquals($id1, $atts['comments'][0]['byuserid']);
        assertNull($atts['comments'][0]['user2']);

        # Get it
        $atts = $u2->getComment($cid);
        assertEquals("Test comment", $atts['user1']);
        assertEquals($id1, $atts['byuserid']);
        assertNull($atts['user2']);
        assertNull($u2->getComment(-1));

        # Edit it
        assertTrue($u2->editComment($cid, "Test comment2"));
        $atts = $u2->getPublic();
        assertEquals(1, count($atts['comments']));
        assertEquals($cid, $atts['comments'][0]['id']);
        assertEquals("Test comment2", $atts['comments'][0]['user1']);

        # Can't see comments when a user
        $u1->setRole(User::ROLE_MEMBER, $gid);
        $atts = $u2->getPublic();
        assertEquals(0, count($atts['comments']));

        # Try to delete a comment when not a mod
        $u1->removeMembership($gid);
        assertFalse($u2->deleteComment($cid));
        $u1->addMembership($gid);
        assertFalse($u2->deleteComment($cid));
        $u1->addMembership($gid, User::ROLE_MODERATOR);
        assertTrue($u2->deleteComment($cid));
        $atts = $u2->getPublic();
        assertEquals(0, count($atts['comments']));

        # Delete all
        $cid = $u2->addComment($gid, "Test comment");
        assertNotNull($cid);
        assertTrue($u2->deleteComments());
        $atts = $u2->getPublic();
        assertEquals(0, count($atts['comments']));

        error_log(__METHOD__ . " end");
    }
}

