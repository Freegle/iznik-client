<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/message/Message.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class userTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE users, users_emails FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE users_emails.email IN ('test@test.com', 'test2@test.com');");
        $dbhm->preExec("DELETE users, users_logins FROM users INNER JOIN users_logins ON users.id = users_logins.userid WHERE uid IN ('testid', '1234');");
        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM users WHERE firstname = 'Test' AND lastname = 'User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort LIKE 'testgroup%';");
        $dbhm->preExec("DELETE FROM users_emails WHERE email = 'bit-bucket@test.smtp.org'");
        $dbhm->preExec("DELETE FROM users_emails WHERE email = 'test@test.com'");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        error_log("Created $id");
        $atts = $u->getPublic();
        assertEquals('Test', $atts['firstname']);
        assertEquals('User', $atts['lastname']);
        assertNull($atts['fullname']);
        assertEquals('Test User', $u->getName());
        assertEquals($id, $u->getPrivate('id'));
        assertNull($u->getPrivate('invalidid'));

        $u->setPrivate('yahooid', 'testyahootest');
        assertEquals($id, $u->findByYahooId('testyahootest'));

        assertGreaterThan(0, $u->delete());

        $u = User::get($this->dbhr, $this->dbhm);
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

        $u = User::get($this->dbhm, $this->dbhm);
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

        # Add a new preferred
        assertGreaterThan(0, $u->addEmail('test3@test.com', 1));
        $emails = $u->getEmails();
        assertEquals(2, count($emails));
        assertEquals(1, $emails[0]['preferred']);
        assertEquals('test3@test.com', $emails[0]['email']);

        # Change to non-preferred.
        assertGreaterThan(0, $u->addEmail('test3@test.com', 0));
        $emails = $u->getEmails();
        error_log("Non-preferred " . var_export($emails, TRUE));
        assertEquals(2, count($emails));
        assertEquals(0, $emails[0]['preferred']);
        assertEquals(0, $emails[1]['preferred']);
        assertEquals('test@test.com', $emails[0]['email']);
        assertEquals('test3@test.com', $emails[1]['email']);

        # Change to preferred.
        assertGreaterThan(0, $u->addEmail('test3@test.com', 1));
        $emails = $u->getEmails();
        assertEquals(2, count($emails));
        assertEquals(1, $emails[0]['preferred']);
        assertEquals('test3@test.com', $emails[0]['email']);

        # Add them as memberships and check we get the right ones.
        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $emailid1 = $u->getIdForEmail('test@test.com')['id'];
        $emailid3 = $u->getIdForEmail('test3@test.com')['id'];
        error_log("emailid1 $emailid1 emailid3 $emailid3");
        $u->addMembership($group1, User::ROLE_MEMBER, $emailid1);
        assertEquals($emailid1, $u->getEmailForYahooGroup($group1, FALSE, TRUE)[0]);
        $u->removeMembership($group1);
        $u->addMembership($group1, User::ROLE_MEMBER, $emailid3);
        $u->addMembership($group1, User::ROLE_MEMBER, $emailid3);
        assertEquals($emailid3, $u->getEmailForYahooGroup($group1, FALSE, TRUE)[0]);
        assertNull($u->getIdForEmail('wibble@test.com'))['id'];
        assertNull($u->getEmailForYahooGroup(-1)[0]);

        error_log(__METHOD__ . " end");
    }

    public function testLogins() {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
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

        $u = User::get($this->dbhr, $this->dbhm);
        assertEquals(0, $u->addEmail('test-owner@yahoogroups.com'));

        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $u->setDbhm($mock);
        $id = $u->create(NULL, NULL, 'Test User');
        assertNull($id);

        error_log(__METHOD__ . " end");
    }

    public function testMemberships() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $this->dbhm->preExec("UPDATE users SET yahooUserId = 1 WHERE id = $id;");
        User::clearCache($id);
        $eid = $u->addEmail('test@test.com');
        assertGreaterThan(0, $eid);
        $u->setPrivate('yahooUserId', 1);
        $u = User::get($this->dbhm, $this->dbhm, $id);
        assertGreaterThan(0, $u->addEmail('test@test.com'));
        assertEquals($u->getRoleForGroup($group1), User::ROLE_NONMEMBER);
        assertFalse($u->isModOrOwner($group1));

        $u->addMembership($group1, User::ROLE_MEMBER, $eid);
        assertEquals($u->getRoleForGroup($group1), User::ROLE_MEMBER);
        assertFalse($u->isModOrOwner($group1));
        $u->setGroupSettings($group1, [
            'testsetting' => 'test'
        ]);
        assertEquals('test', $u->getGroupSettings($group1)['testsetting']);
        $atts = $u->getPublic();
        assertFalse(array_key_exists('applied', $atts));

        error_log("Set owner");
        $u->setRole(User::ROLE_OWNER, $group1);
        assertEquals($u->getRoleForGroup($group1), User::ROLE_OWNER);
        assertTrue($u->isModOrOwner($group1));
        assertTrue(array_key_exists('work', $u->getMemberships(FALSE, NULL, TRUE)[0]));
        $settings = $u->getGroupSettings($group1);
        error_log("Settings " . var_export($settings, TRUE));
        assertEquals('test', $settings['testsetting']);
        assertTrue(array_key_exists('configid', $settings));
        $modships = $u->getModeratorships();
        assertEquals(1, count($modships));

        # Should be able to see the applied history.
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $atts = $u->getPublic();
        error_log("Applied " . var_export($atts['applied'], TRUE));
        assertEquals(1, count($atts['applied']));

        $u->setRole(User::ROLE_MODERATOR, $group1);
        assertEquals($u->getRoleForGroup($group1), User::ROLE_MODERATOR);
        assertTrue($u->isModOrOwner($group1));
        assertTrue(array_key_exists('work', $u->getMemberships(FALSE, NULL, TRUE)[0]));
        $modships = $u->getModeratorships();
        assertEquals(1, count($modships));

        $u->addMembership($group2, User::ROLE_MEMBER, $eid);
        $membs = $u->getMemberships();
        assertEquals(2, count($membs));

        $u->removeMembership($group1);
        assertEquals($u->getRoleForGroup($group1), User::ROLE_NONMEMBER);
        $membs = $u->getMemberships();
        assertEquals(1, count($membs));
        assertEquals($group2, $membs[0]['id']);

        # Plugin work should exist
        $p = new Plugin($this->dbhr, $this->dbhm);
        $work = $p->get($group1);
        assertEquals(1, count($work));
        assertEquals($group1, $work[0]['groupid']);
        assertEquals('{"type":"RemoveApprovedMember","email":"test@test.com"}', $work[0]['data']);
        $pid = $work[0]['id'];
        $p->delete($pid);

        // Support and admin users have a mod role on the group even if not a member
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test item (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'testgroup1@yahoogroups.com', $msg);
        list($mid, $already) = $m->save();
        $m = new Message($this->dbhm, $this->dbhm, $mid);

        $u->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);
        assertEquals($u->getRoleForGroup($group1), User::ROLE_MODERATOR);
        assertEquals(User::ROLE_MODERATOR, $m->getRoleForMessage());
        $u->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);
        error_log("Check role for group");
        assertEquals($u->getRoleForGroup($group1), User::ROLE_OWNER);
        error_log("Check role for message");
        $me = whoAmI($this->dbhr, $this->dbhm);
        $me->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);
        assertEquals(User::SYSTEMROLE_ADMIN, $me->getPrivate('systemrole'));
        assertEquals(User::ROLE_OWNER, $m->getRoleForMessage());

        # Ban ourselves; can't rejoin
        error_log("Ban " . $u->getId() . " from $group2");
        $u->removeMembership($group2, TRUE);
        $membs = $u->getMemberships();
        error_log("Memberships after ban " . var_export($membs, TRUE));

        # Should have the membership of group1, implicitly added because we sent a message from that group.
        assertEquals(1, count($membs));
        assertFalse($u->addMembership($group2));

        $g = Group::get($this->dbhr, $this->dbhm, $group1);
        $g->delete();
        $g = Group::get($this->dbhr, $this->dbhm, $group2);
        $g->delete();

        $membs = $u->getMemberships();
        assertEquals(0, count($membs));

        error_log(__METHOD__ . " end");
    }

    public function testMerge() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);
        $group3 = $g->create('testgroup3', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $id1 = $u->create(NULL, NULL, 'Test User');
        $id2 = $u->create(NULL, NULL, 'Test User');
        $u1 = User::get($this->dbhr, $this->dbhm, $id1);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2);
        assertGreaterThan(0, $u1->addEmail('test1@test.com'));
        assertGreaterThan(0, $u1->addEmail('test2@test.com', 0));

        # Set up various memberships
        $u1->addMembership($group1, User::ROLE_MODERATOR);
        $u2->addMembership($group1, User::ROLE_MEMBER);
        $u2->addMembership($group2, User::ROLE_OWNER);
        $u1->addMembership($group3, User::ROLE_MEMBER);
        $u2->addMembership($group3, User::ROLE_MODERATOR);
        $settings = [ 'test' => 1];
        $u2->setGroupSettings($group2, $settings);
        assertEquals([ 'showmessages' => 1, 'showmembers' => 1, 'pushnotify' => 1, 'showchat' => 1 ], $u1->getGroupSettings($group2));

        # We should get the group back and a default config.
        assertEquals(1, $u2->getGroupSettings($group2)['test'] );
        assertNotNull($u2->getGroupSettings($group2)['configid']);

        # Merge u2 into u1
        assertTrue($u1->merge($id1, $id2, "UT"));

        # Pick up new settings.
        $u1 = User::get($this->dbhr, $this->dbhm, $id1, FALSE);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2, FALSE);

        assertEquals(1, $u1->getGroupSettings($group2)['test'] );
        assertNotNull($u1->getGroupSettings($group2)['configid']);

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

//        Merged logs are hidden.
//        $atts = $u1->getPublic(NULL, FALSE, TRUE);
//        error_log("ID is " . $u1->getId() . " public " . var_export($atts, true));
//        $log = $this->findLog('User', 'Merged', $atts['logs']);
//        assertEquals($id1, $log['user']['id']);

        error_log(__METHOD__ . " end");
    }

    public function testMergeReal() {
        error_log(__METHOD__);

        # Simulates processing from real emails migration script.
        $g = Group::get($this->dbhr, $this->dbhm);
        $group = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $id1 = $u->create(NULL, NULL, 'Test User');
        $id2 = $u->create(NULL, NULL, 'Test User');
        $u1 = User::get($this->dbhr, $this->dbhm, $id1);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2);
        $eid1 = $u1->addEmail('test1@test.com');
        $eid2 = $u2->addEmail('test2@test.com');

        # Set up various memberships
        $u1->addMembership($group, User::ROLE_MEMBER, $eid1);
        $u2->addMembership($group, User::ROLE_MEMBER, $eid2);

        # Merge u2 into u1
        assertTrue($u1->merge($id1, $id2, "UT"));

        # Pick up new settings.
        $u1 = User::get($this->dbhm, $this->dbhm, $id1);

        $membershipid = $this->dbhm->preQuery("SELECT id FROM memberships WHERE userid = ?;", [ $id1 ])[0]['id'];
        error_log("Membershipid $membershipid");

        # Should have both Yahoo memberships.
        $yahoomembers = $this->dbhm->preQuery("SELECT * FROM memberships_yahoo WHERE membershipid = ? ORDER BY emailid;", [ $membershipid ]);
        error_log("Yahoo memberships " . var_export($yahoomembers, TRUE));
        assertEquals($eid1, $yahoomembers[0]['emailid']);
        assertEquals($eid2, $yahoomembers[1]['emailid']);

        error_log(__METHOD__ . " end");
    }


    public function testDoubleAdd() {
        error_log(__METHOD__);

        # Simulates processing from real emails migration script.
        $g = Group::get($this->dbhr, $this->dbhm);
        $group = $g->create('testgroup', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $id1 = $u->create(NULL, NULL, 'Test User');
        $eid1 = $u->addEmail('test1@test.com');
        $eid2 = $u->addEmail('test2@test.com');

        # Set up membership with two emails
        $u->addMembership($group, User::ROLE_MEMBER, $eid1);
        $u->addMembership($group, User::ROLE_MEMBER, $eid2);

        $membershipid = $this->dbhm->preQuery("SELECT id FROM memberships WHERE userid = ?;", [ $id1 ])[0]['id'];
        error_log("Membershipid $membershipid");

        # Should have both Yahoo memberships.
        $yahoomembers = $this->dbhm->preQuery("SELECT * FROM memberships_yahoo WHERE membershipid = ? ORDER BY emailid;", [ $membershipid ]);
        error_log("Yahoo memberships " . var_export($yahoomembers, TRUE));
        self::assertEquals(2, count($yahoomembers));
        assertEquals($eid1, $yahoomembers[0]['emailid']);
        assertEquals($eid2, $yahoomembers[1]['emailid']);

        error_log(__METHOD__ . " end");
    }

    public function testMergeError() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);
        $group3 = $g->create('testgroup3', Group::GROUP_REUSE);

        $u = User::get($this->dbhr, $this->dbhm);
        $id1 = $u->create(NULL, NULL, 'Test User');
        $id2 = $u->create(NULL, NULL, 'Test User');
        $u1 = User::get($this->dbhr, $this->dbhm, $id1);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2);
        assertGreaterThan(0, $u1->addEmail('test1@test.com'));
        assertGreaterThan(0, $u1->addEmail('test2@test.com', 1));

        # Set up various memberships
        $u1->addMembership($group1, User::ROLE_MODERATOR);
        $u2->addMembership($group1, User::ROLE_MEMBER);
        $u2->addMembership($group2, User::ROLE_OWNER);
        $u1->addMembership($group3, User::ROLE_MEMBER);
        $u2->addMembership($group3, User::ROLE_MODERATOR);

        $dbconfig = array (
            'host' => SQLHOST,
            'port' => SQLPORT,
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['database']};charset=utf8";

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ), TRUE))
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $u1->setDbhm($mock);

        # Merge u2 into u1
        assertFalse($u1->merge($id1, $id2, "UT"));

        # Pick up new settings.
        $u1 = User::get($this->dbhr, $this->dbhm, $id1);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2);

        # Both exist
        assertNotNull($u1->getId());
        assertNotNull($u2->getId());

        error_log(__METHOD__ . " end");
    }

    public function testSystemRoleMax() {

        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);

        assertEquals(User::SYSTEMROLE_ADMIN, $u->systemRoleMax(User::SYSTEMROLE_MODERATOR, User::SYSTEMROLE_ADMIN));
        assertEquals(User::SYSTEMROLE_ADMIN, $u->systemRoleMax(User::SYSTEMROLE_ADMIN, User::SYSTEMROLE_SUPPORT));

        assertEquals(User::SYSTEMROLE_SUPPORT, $u->systemRoleMax(User::SYSTEMROLE_MODERATOR, User::SYSTEMROLE_SUPPORT));
        assertEquals(User::SYSTEMROLE_SUPPORT, $u->systemRoleMax(User::SYSTEMROLE_SUPPORT, User::SYSTEMROLE_USER));

        assertEquals(User::SYSTEMROLE_MODERATOR, $u->systemRoleMax(User::SYSTEMROLE_MODERATOR, User::SYSTEMROLE_MODERATOR));
        assertEquals(User::SYSTEMROLE_MODERATOR, $u->systemRoleMax(User::SYSTEMROLE_MODERATOR, User::SYSTEMROLE_USER));

        assertEquals(User::SYSTEMROLE_USER, $u->systemRoleMax(User::SYSTEMROLE_USER, User::SYSTEMROLE_USER));

        error_log(__METHOD__ . " end");
    }

    public function testRoleMax() {

        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);

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

        $u = User::get($this->dbhr, $this->dbhm);

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

        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $g = Group::get($this->dbhr, $this->dbhm);
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
        $c->setPrivate('ccfollmembto', 'Specific');
        $c->setPrivate('ccfollmembaddr', 'test@test.com');

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s->setPrivate('action', 'Reject Member');

        $u->mail($group, "test", "test", $sid);

        $s->delete();

        $s = new StdMessage($this->dbhr, $this->dbhm);
        $sid = $s->create('Test', $cid);
        $s->setPrivate('action', 'Leave Approved Member');

        error_log("Mail them");
        $u->mail($group, "test", "test", $sid, 'Leave Approved Member');

        $s->delete();
        $c->delete();

        error_log(__METHOD__ . " end");
    }

    public function testComments() {
        error_log(__METHOD__);

        $u1 = User::get($this->dbhr, $this->dbhm);
        $id1 = $u1->create('Test', 'User', NULL);
        $u2 = User::get($this->dbhr, $this->dbhm);
        $id2 = $u2->create('Test', 'User', NULL);
        assertGreaterThan(0, $u1->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u1->login('testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
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

    public function testCheck(){
        error_log(__METHOD__);

        $u1 = User::get($this->dbhr, $this->dbhm);
        $id1 = $u1->create('Test', 'User', NULL);
        assertGreaterThan(0, $u1->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u1->login('testpw'));
        $u2 = User::get($this->dbhr, $this->dbhm);
        $id2 = $u2->create('Test', 'User', NULL);

        $g = Group::get($this->dbhr, $this->dbhm);
        $groupids = [];

        for ($i = 0; $i < Spam::SEEN_THRESHOLD + 1; $i++) {
            $gid = $g->create("testgroup$i", Group::GROUP_REUSE);
            $g = Group::get($this->dbhr, $this->dbhm);
            $groupids[] = $gid;
            $u1->addMembership($gid, User::ROLE_MODERATOR);
            $u2->addMembership($gid);

            $u2 = User::get($this->dbhr, $this->dbhm, $id2, FALSE);
            $atts = $u2->getPublic();

            error_log("$i");
            if ($i < Spam::SEEN_THRESHOLD) {
                assertFalse(pres('suspectcount', $atts));
            } else {
                assertEquals(1, pres('suspectcount', $atts));
                $membs = $g->getMembers(10, NULL, $ctx, NULL, MembershipCollection::SPAM, [ $gid ]);
                assertEquals(2, count($membs));
            }
        }

        error_log(__METHOD__ . " end");
    }

    public function testVerifyMail() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = 'localhost';

        # Test add when it's not in use anywhere
        $u1 = User::get($this->dbhr, $this->dbhm);
        $id1 = $u1->create('Test', 'User', NULL);
        $u1 = User::get($this->dbhr, $this->dbhm, $id1);
        assertFalse($u1->verifyEmail('bit-bucket@test.smtp.org'));

        # Confirm it
        $emails = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE email = 'bit-bucket@test.smtp.org';");
        assertEquals(1, count($emails));
        foreach ($emails as $email) {
            assertTrue($u1->confirmEmail($email['validatekey']));
        }

        # Test add when it's in use for another user
        $u2 = User::get($this->dbhr, $this->dbhm);
        $id2 = $u2->create('Test', 'User', NULL);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2);
        assertFalse($u2->verifyEmail('bit-bucket@test.smtp.org'));

        # Now confirm that- should trigger a merge.
        assertGreaterThan(0, $u2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u2->login('testpw'));
        $emails = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE email = 'bit-bucket@test.smtp.org';");
        assertEquals(1, count($emails));
        foreach ($emails as $email) {
            assertTrue($u2->confirmEmail($email['validatekey']));
        }

        # Test add when it's already one of ours.
        assertNotNull($u2->addEmail('test@test.com'));
        assertTrue($u2->verifyEmail('test@test.com'));

        error_log(__METHOD__ . " end");
    }

    public function testCanon() {
        error_log(__METHOD__);

        assertEquals('test@testcom', User::canonMail('test@test.com'));
        assertEquals('test@testcom', User::canonMail('test+fake@test.com'));
        assertEquals('test@usertrashnothingcom', User::canonMail('test-g1@user.trashnothing.com'));
        assertEquals('test@usertrashnothingcom', User::canonMail('test-x1@user.trashnothing.com'));
        assertEquals('test-x1@usertrashnothingcom', User::canonMail('test-x1-x2@user.trashnothing.com'));
        assertEquals('app+test@proxymailfacebookcom', User::canonMail('app+test@proxymail.facebook.com'));

        error_log(__METHOD__ . " end");
    }

    public function testInvent() {
        error_log(__METHOD__);

        # No emails - should invent something.
        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $email = $u->inventEmail();
        error_log("No emails, invented $email");
        assertFalse(strpos($email, 'test'));

        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->addEmail('test@test.com');
        $email = $u->inventEmail();
        error_log("Unusable email, invented $email");
        assertFalse(strpos($email, 'test'));

        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->setPrivate('yahooid', '-wibble');
        $email = $u->inventEmail();
        error_log("Yahoo ID, invented $email");
        assertNotFalse(strpos($email, 'wibble'));

        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u->addEmail('wobble@wobble.com');
        $email = $u->inventEmail();
        error_log("Other email, invented $email");
        assertNotFalse(strpos($email, 'wobble'));

        # Call again now we have one.
        $email2 = $u->inventEmail();
        error_log("Other email again, invented $email2");
        assertEquals($email, $email2);

        error_log(__METHOD__ . " end");
    }
//
//    public function testSpecial() {
//        error_log(__METHOD__);
//
//        $u = User::get($this->dbhr, $this->dbhm);
//        $uid = $u->findByEmail('chris@phdcc.com');
//        $u = User::get($this->dbhr, $this->dbhm, $uid);
//
//        list ($eidforgroup, $emailforgroup) = $u->getEmailForYahooGroup(21560, TRUE, TRUE);
//        error_log("Eid is $eidforgroup");
//
//        error_log(__METHOD__ . " end");
//    }
}

