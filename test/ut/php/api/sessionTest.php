<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class sessionTest extends IznikAPITest {
    public function testLoggedOut() {
        error_log(__METHOD__);

        $ret = $this->call([
            'call' => 'session_get'
        ]);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testYahoo() {
        error_log(__METHOD__);

        # Logged out should cause redirect
        $ret = $this->call([
            'call' => 'session_login',
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

        $ret = $this->call([
            'call' => 'session_login',
            'yahoologin' => 1
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call([
            'call' => 'session_get'
        ]);
        assertEquals(0, $ret['ret']);

        $mock->method('getAttributes')->willThrowException(new Exception());
        $ret = $this->call([
            'call' => 'session_login',
            'yahoologin' => 1
        ]);
        assertEquals(2, $ret['ret']);

        # Logout
        $ret = $this->call([
            'call' => 'session_logout'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call([
            'call' => 'session_logout'
        ]);
        assertEquals(0, $ret['ret']);

        # Should be logged out
        $ret = $this->call([
            'call' => 'session_get',
            'yahoologin' => 1
        ]);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

