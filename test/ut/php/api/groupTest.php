<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';
require_once(IZNIK_BASE . '/include/config/ModConfig.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class groupAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test';");
        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup2';");
        $dbhm->preExec("DELETE FROM users WHERE yahooUserId = 1;");

        # Create a moderator and log in as them
        $g = new Group($this->dbhr, $this->dbhm);
        $this->group = $g;
        $this->groupid = $g->create('testgroup', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $emailid = $this->user->addEmail('test@test.com');
        $this->user->addMembership($this->groupid, User::ROLE_MEMBER, $emailid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
    }

    protected function tearDown() {
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");

        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testCreate()
    {
        error_log(__METHOD__);

        # Not logged in - should fail
        $ret = $this->call('group', 'POST', [
            'action' => 'Create',
            'grouptype' => 'Reuse',
            'name' => 'testgroup'
        ]);
        assertEquals(1, $ret['ret']);

        # Logged in
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('group', 'POST', [
            'action' => 'Create',
            'grouptype' => 'Reuse',
            'name' => 'testgroup2'
        ]);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);

        error_log(__METHOD__ . " end");
    }

    public function testGet() {
        error_log(__METHOD__);

        # Not logged in - shouldn't see members list
        $ret = $this->call('group', 'GET', [
            'id' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($this->groupid, $ret['group']['id']);
        assertFalse(pres('members', $ret['group']));

        # By short name
        $ret = $this->call('group', 'GET', [
            'id' => 'testgroup',
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($this->groupid, $ret['group']['id']);
        assertFalse(pres('members', $ret['group']));

        # Duff shortname
        $ret = $this->call('group', 'GET', [
            'id' => 'testinggroup',
            'members' => TRUE
        ]);
        assertEquals(2, $ret['ret']);

        # Member - shouldn't see members list
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('group', 'GET', [
            'id' => $this->groupid,
            'members' => TRUE
        ]);
        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertFalse(pres('members', $ret['group']));

        # Moderator - should see members list
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('group', 'GET', [
            'id' => $this->groupid,
            'members' => TRUE
        ]);
        error_log("Members " . var_export($ret, true));
        assertEquals(0, $ret['ret']);

        assertEquals(1, count($ret['group']['members']));
        assertEquals('test@test.com', $ret['group']['members'][0]['email']);

        error_log(__METHOD__ . " end");
    }

    public function testPatch() {
        error_log(__METHOD__);

        # Not logged in - shouldn't be able to set
        $ret = $this->call('group', 'PATCH', [
            'id' => $this->groupid,
            'settings' => [
                'mapzoom' => 12
            ]
        ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Member - shouldn't either
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('group', 'PATCH', [
            'id' => $this->groupid,
            'settings' => [
                'mapzoom' => 12
            ]
        ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Owner - should be able to
        $this->user->setRole(User::ROLE_OWNER, $this->groupid);
        $ret = $this->call('group', 'PATCH', [
            'id' => $this->groupid,
            'settings' => [
                'mapzoom' => 12
            ]
        ]);

        $ret = $this->call('group', 'GET', [
            'id' => $this->groupid
        ]);
        assertEquals(12, $ret['group']['settings']['mapzoom']);

        error_log(__METHOD__ . " end");
    }

    public function testConfirmMod() {
        error_log(__METHOD__);

        $ret = $this->call('group', 'POST', [
            'action' => 'ConfirmKey',
            'id' => $this->groupid
        ]);
        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);
        $key = $ret['key'];

        error_log(__METHOD__ . " end");
    }

    public function testAddLicense() {
        error_log(__METHOD__);

        # Not logged in
        $ret = $this->call('group', 'POST', [
            'action' => 'AddLicense',
            'id' => $this->groupid,
            'voucher' => 'wibble'
        ]);
        assertEquals(1, $ret['ret']);

        # Invalid voucher
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('group', 'POST', [
            'action' => 'AddLicense',
            'id' => $this->groupid,
            'voucher' => 'wibble'
        ]);
        assertEquals(2, $ret['ret']);

        $voucher = $this->group->createVoucher();
        $ret = $this->call('group', 'POST', [
            'action' => 'AddLicense',
            'id' => $this->groupid,
            'voucher' => $voucher
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testContact() {
        error_log(__METHOD__);

        $ret = $this->call('groups', 'GET', [
            'grouptype' => Group::GROUP_OTHER
        ]);
        assertEquals(1, $ret['ret']);

        # TODO Would be better if this were generic, but we can't easily mock out the actual mail call when testing
        # the API.
        assertTrue($this->user->login('testpw'));

        $ret = $this->call('groups', 'GET', [
            'grouptype' => Group::GROUP_OTHER
        ]);
        assertEquals(1, $ret['ret']);

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);

        $ret = $this->call('groups', 'GET', [
            'grouptype' => Group::GROUP_OTHER
        ]);
        assertEquals(0, $ret['ret']);
        assertGreaterThan(0, count($ret['groups']));

        $gid = $this->group->findByShortName('FreeglePlayground');
        $ret = $this->call('group', 'POST', [
            'action' => 'Contact',
            'id' => $gid,
            'from' => 'info',
            'subject' => 'Test - please ignore',
            'body' => 'This message was sent from the Iznik Unit Test to the FreeglePlayground group owners.  Please ignore it - or leave the group.'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('group', 'POST', [
            'action' => 'Contact',
            'id' => $gid,
            'from' => 'support',
            'subject' => 'Test - please ignore',
            'body' => 'This message was sent from the Iznik Unit Test to the FreeglePlayground group owners.  Please ignore it - or leave the group.'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('group', 'POST', [
            'action' => 'Contact',
            'id' => $gid,
            'from' => 'invalid',
            'subject' => 'Test - please ignore',
            'body' => 'This message was sent from the Iznik Unit Test to the FreeglePlayground group owners.  Please ignore it - or leave the group.'
        ]);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

