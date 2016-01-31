<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';
require_once IZNIK_BASE . '/include/misc/Location.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class spammersAPITest extends IznikAPITest {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM spam_users WHERE reason LIKE 'Test reason%';");
        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_emails WHERE email IN ('test@test.com', 'test2@test.com'));");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");

        $this->group = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_REUSE);

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addEmail('test2@test.com');
        assertEquals(1, $this->user->addMembership($this->groupid));
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $this->user->addEmail('test2@test.com');

        # Add them to a group, so that when they get onto a list we can trigger their removal.
        assertTrue($u->addMembership($this->groupid));

        $ret = $this->call('spammers', 'GET', [
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['spammers']));

        # Things we can't do when not logged in
        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_SPAMMER,
            'reason' => 'Test reason'
        ]);
        assertEquals(1, $ret['ret']);

        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_PENDING_REMOVE,
            'reason' => 'Test reason',
            'dup' => 1
        ]);
        assertEquals(1, $ret['ret']);

        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_PENDING_ADD,
            'reason' => 'Test reason',
            'dup' => 2
        ]);
        assertEquals(1, $ret['ret']);

        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_WHITELIST,
            'reason' => 'Test reason',
            'dup' => 3
        ]);
        assertEquals(1, $ret['ret']);

        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => 'wibble',
            'reason' => 'Test reason'
        ]);
        assertEquals(1, $ret['ret']);

        # Anyone logged in can report
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($this->user->login('testpw'));

        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_PENDING_ADD,
            'reason' => 'Test reason',
            'dup' => 4
        ]);

        assertEquals(0, $ret['ret']);
        $sid = $ret['id'];
        assertNotNull($sid);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_SPAMMER,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['spammers']));

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_PENDING_ADD,
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['spammers']));
        assertEquals($sid, $ret['spammers'][0]['id']);
        assertEquals($uid, $ret['spammers'][0]['userid']);

        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_WHITELIST,
            'reason' => 'Test reason',
            'dup' => 66
        ]);

        assertEquals(2, $ret['ret']);

        # Look at the pending queue
        $this->user->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_SPAMMER,
            'reason' => 'Test reason',
            'dup' => 5
        ]);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_SPAMMER,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['spammers']));

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_PENDING_ADD,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['spammers']));

        # Trigger removal

        # Request removal
        $this->user->setPrivate('systemrole', User::SYSTEMROLE_MODERATOR);

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_PENDING_REMOVE,
            'reason' => 'Test reason',
            'dup' => 6
        ]);

        assertEquals(0, $ret['ret']);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_PENDING_REMOVE,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['spammers']));

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_WHITELIST,
            'reason' => 'Test reason',
            'dup' => 7
        ]);

        assertEquals(2, $ret['ret']);

        $ret = $this->call('spammers', 'DELETE', [
            'id' => $sid
        ]);

        assertEquals(2, $ret['ret']);

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_WHITELIST,
            'reason' => 'Test reason',
            'dup' => 77
        ]);

        assertEquals(0, $ret['ret']);

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_PENDING_ADD,
            'reason' => 'Test reason',
            'dup' => 81
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_PENDING_REMOVE,
            'reason' => 'Test reason',
            'dup' => 81
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('spammers', 'PATCH', [
            'id' => $sid,
            'collection' => Spam::TYPE_SPAMMER,
            'reason' => 'Test reason',
            'dup' => 81
        ]);
        assertEquals(0, $ret['ret']);

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);

        $ret = $this->call('spammers', 'DELETE', [
            'id' => $sid
        ]);

        assertEquals(0, $ret['ret']);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_SPAMMER,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['spammers']));

        error_log(__METHOD__ . " end");
    }
}

