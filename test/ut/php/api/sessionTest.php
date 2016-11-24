<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/user/Notifications.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class sessionTest extends IznikAPITestCase
{
    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->preExec("DELETE FROM users_push_notifications WHERE `type` = 'Test';");
    }

    public function testLoggedOut()
    {
        error_log(__METHOD__);

        $ret = $this->call('session', 'GET', []);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }


    public function testLargeRequest()
    {
        error_log(__METHOD__);

        $str = '';
        while (strlen($str) < 200000) {
            $str .= '1234123412';
        }

        $ret = $this->call('session', 'POST', [
            'junk' => $str
        ]);

        error_log(__METHOD__ . " end");
    }

    public function testYahoo()
    {
        error_log(__METHOD__);

        # Logged out should cause redirect
        $ret = $this->call('session', 'POST', [
            'yahoologin' => 1
        ]);
        assertEquals(1, $ret['ret']);
        assertTrue(array_key_exists('redirect', $ret));

        # Login.  Create Yahoo class then mock it.
        $y = Yahoo::getInstance($this->dbhr, $this->dbhm);
        $email = 'test' . microtime() . '@test.com';
        $mock = $this->getMockBuilder('LightOpenID')
            ->disableOriginalConstructor()
            ->setMethods(array('validate', 'getAttributes'))
            ->getMock();
        $mock->method('validate')->willReturn(true);
        $mock->method('getAttributes')->willReturn([
            'contact/email' => $email,
            'name' => 'Test User'
        ]);
        $y->setOpenid($mock);

        $ret = $this->call('session', 'POST', [
            'yahoologin' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);

        $mock->method('getAttributes')->willThrowException(new Exception());
        $ret = $this->call('session', 'POST', [
            'yahoologin' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Logout
        $ret = $this->call('session', 'DELETE', []);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('session', 'DELETE', []);
        assertEquals(0, $ret['ret']);#

        # Should be logged out
        $ret = $this->call('session', 'GET', []);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testFacebook()
    {
        error_log(__METHOD__);

        # With no token should fail.
        $ret = $this->call('session', 'POST', [
            'fblogin' => 1
        ]);
        assertEquals(2, $ret['ret']);
        
        # Rest of testing done in include test.

        error_log(__METHOD__ . " end");
    }

    public function testGoogle()
    {
        error_log(__METHOD__);

        # With no token should fail.
        $ret = $this->call('session', 'POST', [
            'googlelogin' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Rest of testing done in include test.

        error_log(__METHOD__ . " end");
    }

    public function testNative()
    {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test@test.com'));
        $u = User::get($this->dbhm, $this->dbhm, $id);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $group1);
        $g->setPrivate('welcomemail', 'Test - please ignore');
        $u->addMembership($group1);

        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        error_log("Session get");
        $ret = $this->call('session', 'GET', []);
        error_log("Session got");
        assertEquals(0, $ret['ret']);
        assertEquals($group1, $ret['groups'][0]['id']);
        assertEquals('test@test.com', $ret['emails'][0]['email']);

        # Set something
        $ret = $this->call('session', 'PATCH', [
            'settings' => json_encode(['test' => 1]),
            'displayname' => "Testing User",
            'email' => 'test2@test.com',
            'onholidaytill' => ISODate('@' . time()),
            'notifications' => [
                'push' => [
                    'type' => 'Google',
                    'subscription' => 'Test'
                ]
            ]
        ]);
        assertEquals(10, $ret['ret']);
        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        error_log(var_export($ret, true));
        assertEquals('{"test":1}', $ret['me']['settings']);
        assertEquals('Testing User', $ret['me']['displayname']);
        assertEquals('test@test.com', $ret['me']['email']);

        # Confirm it
        $emails = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE email = 'test2@test.com';");
        assertEquals(1, count($emails));
        foreach ($emails as $email) {
            $ret = $this->call('session', 'PATCH', [
                'key' => 'wibble'
            ]);
            assertEquals(11, $ret['ret']);

            $ret = $this->call('session', 'PATCH', [
                'key' => $email['validatekey']
            ]);
            assertEquals(0, $ret['ret']);
        }

        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        error_log("Confirmed " . var_export($ret, TRUE));
        assertEquals('test2@test.com', $ret['me']['email']);

        $ret = $this->call('session', 'PATCH', [
            'settings' => ['test' => 1],
            'displayname' => "Testing User",
            'email' => 'test2@test.com',
            'notifications' => [
                'push' => [
                    'type' => 'Firefox',
                    'subscription' => 'Test'
                ]
            ]
        ]);
        assertEquals(0, $ret['ret']);

        # Quick test for notification coverage.
        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('curl_exec'))
            ->getMock();
        $mock->method('curl_exec')->willReturn('NotRegistered');
        $mock->notify($id);

        $mock = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhr, $this->dbhm, $id))
            ->setMethods(array('uthook'))
            ->getMock();
        $mock->method('uthook')->willThrowException(new Exception());
        $mock->notify($id);

        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testPatch()
    {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test@test.com'));
        $u = User::get($this->dbhm, $this->dbhm, $id);

        $ret = $this->call('session', 'PATCH', [
            'firstname' => 'Test2',
            'lastname' => 'User2'
        ]);
        assertEquals(1, $ret['ret']);

        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('session', 'PATCH', [
            'firstname' => 'Test2',
            'lastname' => 'User2'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('session', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals('Test2', $ret['me']['firstname']);
        assertEquals('User2', $ret['me']['lastname']);

        # Set to an email already in use
        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test3@test.com'));
        $ret = $this->call('session', 'PATCH', [
            'settings' => json_encode(['test' => 1]),
            'email' => 'test3@test.com'
        ]);
        assertEquals(10, $ret['ret']);

        # Change password and check it works.
        $u = User::get($this->dbhm, $this->dbhm, $id);
        $u->addLogin(User::LOGIN_NATIVE, $u->getId(), 'testpw');
        $ret = $this->call('session', 'POST', [
            'email' =>'test3@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('session', 'PATCH', [
            'password' => 'testpw2'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('session', 'POST', [
            'email', 'test3@test.com',
            'password' => 'testpw2'
        ]);

        $u->delete();

        error_log(__METHOD__ . " end");
    }

    public function testWork()
    {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        error_log("Created user $id");
        assertNotNull($u->addEmail('test@test.com'));
        $u = User::get($this->dbhm, $this->dbhm, $id);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);
        $g1 = Group::get($this->dbhr, $this->dbhm, $group1);
        $g2 = Group::get($this->dbhr, $this->dbhm, $group2);
        $u->addMembership($group1, User::ROLE_MODERATOR);
        $u->addMembership($group2, User::ROLE_MODERATOR);

        # Send one message to pending on each.
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        list($id, $already) = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup2', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        list($id, $already) = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('session', 'GET', []);
        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertEquals($group1, $ret['groups'][0]['id']);
        assertEquals($group2, $ret['groups'][1]['id']);
        assertEquals(1, $ret['groups'][0]['work']['pending']);
        assertEquals(1, $ret['groups'][1]['work']['pending']);
        assertEquals(0, $ret['groups'][0]['work']['spam']);
        assertEquals(0, $ret['groups'][1]['work']['spam']);
        assertEquals(2, $ret['work']['pending']);
        assertEquals(0, $ret['work']['spam']);

        $g1->delete();
        $g2->delete();

        error_log(__METHOD__ . " end");
    }

    public function testPartner()
    {
        error_log(__METHOD__);

        $key = randstr(64);
        $id = $this->dbhm->preExec("INSERT INTO partners_keys (`partner`, `key`) VALUES ('UT', ?);", [$key]);
        assertNotNull($id);
        assertFalse(partner($this->dbhr, 'wibble'));
        assertTrue(partner($this->dbhr, $key));

        $this->dbhm->preExec("DELETE FROM partners_keys WHERE partner = 'UT';");

        error_log(__METHOD__ . " end");
    }

    public function testPushCreds()
    {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        error_log("Created user $id");

        $n = new Notifications($this->dbhr, $this->dbhm);
        assertTrue($n->add($id, Notifications::PUSH_TEST, 'test'));
        
        $ret = $this->call('session', 'GET', []);
        assertEquals(1, $ret['ret']);

        # Normally this would be in a separate API call, so we need to override here.
        global $sessionPrepared;
        $sessionPrepared = FALSE;

        # Now log in using our creds
        $ret = $this->call('session', 'GET', [
            'pushcreds' => 'test'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['me']['id']);

        assertEquals(1, $n->remove($id));

        error_log(__METHOD__ . " end");
    }

    public function testLostPassword() {
        error_log(__METHOD__);

        $email = 'test-' . rand() . '@blackhole.io';

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');

        $ret = $this->call('session', 'POST', [
            'email' => $email,
            'action' => 'LostPassword'
        ]);
        assertEquals(2, $ret['ret']);

        $u->addEmail($email);

        $ret = $this->call('session', 'POST', [
            'email' => $email,
            'action' => 'LostPassword'
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}
