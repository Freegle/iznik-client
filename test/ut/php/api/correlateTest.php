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
class correlateTest extends IznikAPITest {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testPending() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $msgid = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        # Create a mod
        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $u = new User($this->dbhr, $this->dbhm, $id);
        $u->addMembership($group1, User::ROLE_MODERATOR);

        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Pending',
                'Spam'
            ]
        ]);
        assertEquals(1, $ret['ret']);

        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        # Test same - none missing.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Pending',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test@test.com',
                    'subject' => 'Basic test',
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ],
            'wibble' => 'bypass dup check'
        ]);

        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));

        # Test different sender - should be missing.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Pending',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test',
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ]
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['missingonserver']));
        assertEquals('test1@test.com', $ret['missingonserver'][0]['email']);

        # Test different time - should be missing.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Pending',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test',
                    'date' => isodate('21 Aug 2015 10:45:58 +0000')
                ]
            ]
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['missingonserver']));
        assertEquals('2015-08-21T10:45:58Z', $ret['missingonserver'][0]['date']);

        $u->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }
}

