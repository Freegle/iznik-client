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
class groupAPITest extends IznikAPITest {
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
    }

    protected function tearDown() {
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");

        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testGet() {
        error_log(__METHOD__);

        # Not logged in - shouldn't see members list
        $ret = $this->call('group', 'GET', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Member - shouldn't see members list
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('group', 'GET', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Moderator - should see members list
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
        $ret = $this->call('group', 'GET', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(0, $ret['ret']);

        assertEquals(1, count($ret['members']));
        assertEquals('test@test.com', $ret['members'][0]['emails'][0]['email']);

        error_log(__METHOD__ . " end");
    }

    public function testPost() {
        error_log(__METHOD__);

        # Not logged in - shouldn't see members list
        $ret = $this->call('group', 'POST', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Member - shouldn't see members list
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('group', 'POST', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        assertEquals(1, $ret['ret']);
        assertFalse(pres('members', $ret));

        # Moderator - should see members list
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);
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
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'UNMODERATED',
                'yahooDeliveryType' => 'SINGLE',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ],
            [
                'email' => 'test3@test.com',
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'PROHIBITED',
                'yahooDeliveryType' => 'DIGEST',
                'name' => 'Test User',
                'yahooModeratorStatus' => 'OWNER',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ]
        ];

        $ret = $this->call('group', 'POST', [
            'groupid' => $this->groupid,
            'members' => $members
        ]);
        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);

        $ret = $this->call('group', 'GET', [
            'groupid' => $this->groupid,
            'members' => TRUE
        ]);
        error_log(var_export($ret, true));

        assertEquals(3, count($ret['members']));
        assertEquals('test@test.com', $ret['members'][0]['emails'][0]['email']);
        assertEquals('Moderator', $ret['members'][0]['role']);
        assertEquals('test2@test.com', $ret['members'][1]['emails'][0]['email']);
        assertEquals('Member', $ret['members'][1]['role']);
        assertEquals('test3@test.com', $ret['members'][2]['emails'][0]['email']);
        assertEquals('Owner', $ret['members'][2]['role']);
        assertEquals(2, $ret['group']['nummods']);
        
        error_log(__METHOD__ . " end");
    }

    public function testLarge() {
        error_log(__METHOD__);

        $size = 3100;

        assertTrue($this->user->login('testpw'));
        $this->user->setRole(User::ROLE_MODERATOR, $this->groupid);

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
            $members[] = [
                'email' => "test$i@test.com",
                'yahooUserId' => 1,
                'yahooPostingStatus' => 'UNMODERATED',
                'yahooDeliveryType' => 'SINGLE',
                'name' => 'Test User',
                'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
            ];
        };

        $ret = $this->call('group', 'POST', [
            'groupid' => $this->groupid,
            'members' => $members
        ]);
        assertEquals(0, $ret['ret']);

        $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = ?;";
        $counts = $this->dbhr->preQuery($sql, [ $this->groupid ]);
        assertEquals($size + 1, $counts[0]['count']);

        error_log(__METHOD__ . " end");
    }
}

