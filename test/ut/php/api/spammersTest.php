<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';
require_once IZNIK_BASE . '/include/misc/Location.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class spammersAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM spam_users WHERE reason LIKE 'Test reason%';");
        $dbhm->preExec("DELETE users, users_emails FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE email IN ('test@test.com', 'test2@test.com', 'test3@test.com', 'test4@test.com');");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");

        $this->group = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $this->group->create('testgroup', Group::GROUP_FREEGLE);

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addEmail('test2@test.com');
        assertEquals(1, $this->user->addMembership($this->groupid));
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        # Delete any UT playground messages
        $g = new Group($dbhr, $dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $sql = "DELETE FROM messages_groups WHERE groupid = $gid AND yahooapprovedid < 500;";
        $this->dbhm->preExec($sql);
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
        assertGreaterThan(0, $u->addEmail('test3@test.com'));
        assertGreaterThan(0, $u->addEmail('test4@test.com'));

        # Add them to a group, so that when they get onto a list we can trigger their removal.
        assertTrue($u->addMembership($this->groupid));

        # And create a message from them, so that gets removed too.
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();
        error_log("Created message $id");
        $m = new Message($this->dbhr, $this->dbhm, $id);
        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $this->dbhm->preExec("UPDATE messages SET fromuser = ? WHERE id = ?;", [ $uid, $id ]);
        $this->dbhm->preExec("UPDATE messages_groups SET groupid = ? WHERE msgid = ?;", [ $this->groupid, $id ]);

        $ret = $this->call('spammers', 'GET', [
            'search' => 'Test User'
        ]);
        assertEquals(2, $ret['ret']);

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

        $this->user->setPrivate('systemrole', User::SYSTEMROLE_MODERATOR);
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
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertTrue($found);
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertTrue($found);

        $ret = $this-> call('spammers', 'POST', [
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
        error_log("Should be on list ". var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['spammers']));

        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertTrue($found);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_PENDING_ADD,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertFalse($found);

        # Trigger removal
        $membs = $u->getMemberships();
        error_log("Memberships " . var_export($membs, TRUE));
        assertEquals(User::ROLE_MEMBER, $membs[0]['role']);
        $s = new Spam($this->dbhr, $this->dbhm);
        assertEquals(2, $s->removeSpamMembers($this->groupid));

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
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertTrue($found);

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
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertFalse($found);

        # Report directly to whitelist
        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_WHITELIST,
            'reason' => 'Test reason',
            'dup' => 82
        ]);

        assertEquals(0, $ret['ret']);
        $sid = $ret['id'];

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_WHITELIST,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertTrue($found);

        # Try reporting as a pending spammer - should fail as on whitelist, leaving them still on the whitelist.
        $ret = $this->call('spammers', 'POST', [
            'userid' => $uid,
            'collection' => Spam::TYPE_PENDING_ADD,
            'reason' => 'Test reason',
            'dup' => 83
        ]);

        assertEquals(0, $ret['ret']);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_WHITELIST,
            'search' => 'Test User'
        ]);
        assertEquals(0, $ret['ret']);
        $found = FALSE;

        foreach ($ret['spammers'] as $spammer) {
            if ($spammer['id'] == $sid && $spammer['userid'] == $uid) {
                $found = TRUE;
            }
        }

        assertTrue($found);

        $ret = $this->call('spammers', 'DELETE', [
            'id' => $sid
        ]);

        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testExport() {
        error_log(__METHOD__);

        $key = randstr(64);
        $id = $this->dbhm->preExec("INSERT INTO partners_keys (`partner`, `key`) VALUES ('UT', ?);", [$key]);
        assertNotNull($id);

        $ret = $this->call('spammers', 'GET', [
            'collection' => Spam::TYPE_SPAMMER,
            'partner' => $key,
            'action' => 'export'
        ]);
        assertEquals(0, $ret['ret']);
        assertGreaterThan(0, count($ret['spammers']));

        $this->dbhm->preExec("DELETE FROM partners_keys WHERE partner = 'UT';");

        error_log(__METHOD__ . " end");
    }
}

