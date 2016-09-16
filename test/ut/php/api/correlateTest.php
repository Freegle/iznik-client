<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class correlateTest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $dbhm->preExec("DELETE FROM users WHERE yahooUserId = '1';");
        $dbhm->preExec("DELETE FROM messages_history WHERE fromaddr = 'test@ilovefreegle.org';");
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
        $msg = file_get_contents('msgs/approve');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $msgid = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $msgid);

        # Create a mod
        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $id);
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
                    'yahoopendingid' => 833,
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ],
            'wibble' => 'bypass dup check'
        ]);
        error_log(var_export($ret, true));

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));

        # Test different pending id - should be missing.
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
                    'yahoopendingid' => 832,
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ]
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['missingonserver']));
        assertEquals('test1@test.com', $ret['missingonserver'][0]['email']);

        $u->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testApproved() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/fromyahoo');
        $origmsg = $msg;
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $msgid = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $msgid);

        # Create a mod
        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $id);
        $u->addMembership($group1, User::ROLE_MODERATOR);

        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Approved',
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
                'Approved',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test@test.com',
                    'subject' => 'Basic test 1',
                    'yahooapprovedid' => 1,
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ],
            'wibble' => 'bypass dup check'
        ]);
        error_log(var_export($ret, true));

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));

        # Test different approved id - should be missing.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Approved',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 2',
                    'yahooapprovedid' => 2,
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ]
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['missingonserver']));
        assertEquals('test1@test.com', $ret['missingonserver'][0]['email']);

        # Test missing on client
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Approved',
                'Spam'
            ],
            'messages' => [
            ],
            'wibble' => 'bypass dup check'
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));
        assertEquals(1, count($ret['missingonclient']));
        assertEquals(isodate('Sat, 22 Aug 2015 10:45:58 +0000'), $ret['missingonclient'][0]['date']);

        # Now test with multiple messages.
        error_log("Test multiple");
        $msg = str_ireplace('freegleplayground', 'testgroup', $origmsg);
        $msg = str_ireplace('Date: Sat, 22 Aug 2015 10:45:58 +0000', 'Date: Sat, 20 Aug 2015 10:45:58 +0000', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $msg = $this->unique($msg);
        $msg = preg_replace('/X-Yahoo-Newman-ID: (.*)/i', 'X-Yahoo-Newman-ID: 19440136-m2', $msg);
        $msg = str_replace('Subject: [Test Group] Basic test', 'Subject: [Test Group] Basic test 2', $msg);
        error_log("First $msg");
        $msgid = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = str_ireplace('freegleplayground', 'testgroup', $origmsg);
        $msg = str_ireplace('Date: Sat, 22 Aug 2015 10:45:58 +0000', 'Date: Sat, 21 Aug 2015 10:45:58 +0000', $msg);
        $msg = $this->unique($msg);
        $msg = preg_replace('/X-Yahoo-Newman-ID: (.*)/i', 'X-Yahoo-Newman-ID: 19440136-m3', $msg);
        $msg = str_replace('Subject: [Test Group] Basic test', 'Subject: [Test Group] Basic test 3', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        error_log("Second $msg");
        $msgid = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Now test one missing in the middle.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Approved',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 1',
                    'yahooapprovedid' => 1,
                    'date' => isodate('Sat, 20 Aug 2015 10:45:58 +0000')
                ],
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 3',
                    'yahooapprovedid' => 3,
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ]
        ]);
        error_log(var_export($ret, true));

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));
        assertEquals(1, count($ret['missingonclient']));
        assertEquals(isodate('Sat, 21 Aug 2015 10:45:58 +0000'), $ret['missingonclient'][0]['date']);

        # Now test one missing from the end.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Approved',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 1',
                    'yahooapprovedid' => 1,
                    'date' => isodate('Sat, 20 Aug 2015 10:45:58 +0000')
                ],
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 2',
                    'yahooapprovedid' => 2,
                    'date' => isodate('Sat, 21 Aug 2015 10:45:58 +0000')
                ]
            ],
            'wibble' => "Defeat dup"
        ]);
        error_log(var_export($ret, true));

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));
        assertEquals(1, count($ret['missingonclient']));
        assertEquals(isodate('Sat, 22 Aug 2015 10:45:58 +0000'), $ret['missingonclient'][0]['date']);

        # Now test one missing from the start.
        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Approved',
                'Spam'
            ],
            'messages' => [
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 2',
                    'yahooapprovedid' => 2,
                    'date' => isodate('Sat, 21 Aug 2015 10:45:58 +0000')
                ],
                [
                    'email' => 'test1@test.com',
                    'subject' => 'Basic test 3',
                    'yahooapprovedid' => 3,
                    'date' => isodate('Sat, 22 Aug 2015 10:45:58 +0000')
                ]
            ],
            'wibble' => "Defeat dup2"
        ]);
        error_log(var_export($ret, true));

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));
        assertEquals(1, count($ret['missingonclient']));
        assertEquals(isodate('Sat, 20 Aug 2015 10:45:58 +0000'), $ret['missingonclient'][0]['date']);

        # Now move a message into spam, and test correlate on pending, to make sure the spam approved message
        # doesn't get returned for spam pending.
        $this->dbhm->preExec("UPDATE messages_groups SET collection = 'Spam' WHERE msgid = ?;", [ $msgid ]);

        $ret = $this->call('messages', 'POST', [
            'groupid' => $group1,
            'collections' => [
                'Pending',
                'Spam'
            ],
            'messages' => [
            ]
        ]);
        error_log("Spam and pending " . var_export($ret, true));

        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['missingonserver']));
        assertEquals(0, count($ret['missingonclient']));

        $u->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }
}

