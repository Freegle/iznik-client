<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class sessionTest extends IznikAPITestCase {
    public function testLoggedOut() {
        error_log(__METHOD__);

        $ret = $this->call('session', 'GET', []);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testYahoo() {
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
        $ret = $this->call('session','DELETE', []);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('session','DELETE', []);
        assertEquals(0, $ret['ret']);#

        # Should be logged out
        $ret = $this->call('session','GET', []);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testNative() {
        error_log(__METHOD__);

        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test@test.com'));
        $u = new User($this->dbhm, $this->dbhm, $id);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = new Group($this->dbhr, $this->dbhm, $group1);
        $u->addMembership($group1);

        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('session','GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals($group1, $ret['groups'][0]['id']);
        assertEquals('test@test.com', $ret['emails'][0]['email']);

        # Set something
        $ret = $this->call('session', 'PATCH', [
            'settings' => json_encode([ 'test' => 1]),
            'email' => 'test2@test.com'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('session','GET', []);
        assertEquals(0, $ret['ret']);
        error_log(var_export($ret, true));
        assertEquals('{"test":1}', $ret['me']['settings']);
        assertEquals('test2@test.com', $ret['me']['email']);

        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testPatch() {
        error_log(__METHOD__);

        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test@test.com'));
        $u = new User($this->dbhm, $this->dbhm, $id);

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

        $ret = $this->call('session','GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals('Test2', $ret['me']['firstname']);
        assertEquals('User2', $ret['me']['lastname']);

        # Set to an email already in use
        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test3@test.com'));
        $ret = $this->call('session', 'PATCH', [
            'settings' => json_encode([ 'test' => 1]),
            'email' => 'test3@test.com'
        ]);
        assertEquals(3, $ret['ret']);

        $u->delete();

        error_log(__METHOD__ . " end");
    }

    public function testWork() {
        error_log(__METHOD__);

        $u = new User($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        assertNotNull($u->addEmail('test@test.com'));
        $u = new User($this->dbhm, $this->dbhm, $id);

        $g = new Group($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $group2 = $g->create('testgroup2', Group::GROUP_REUSE);
        $g1 = new Group($this->dbhr, $this->dbhm, $group1);
        $g2 = new Group($this->dbhr, $this->dbhm, $group2);
        $u->addMembership($group1, User::ROLE_MODERATOR);
        $u->addMembership($group2, User::ROLE_MODERATOR);

        # Send one message to pending on each.
        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup2', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $id = $m->save();

        $r = new MailRouter($this->dbhr, $this->dbhm, $id);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $ret = $this->call('session', 'POST', [
            'email' => 'test@test.com',
            'password' => 'testpw'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('session','GET', []);
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
}

