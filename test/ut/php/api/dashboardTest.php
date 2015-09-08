<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class dashboardTest extends IznikAPITest {
    public function testLoggedOut() {
        error_log(__METHOD__);

        $ret = $this->call('session', 'GET', []);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testAdmin() {
        error_log(__METHOD__);

        # Logged out
        $ret = $this->call('dashboard', 'GET', []);
        assertEquals(1, $ret['ret']);

        # Now log in
        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u = new User($this->dbhr, $this->dbhm, $id);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        error_log("After login {$_SESSION['id']}");

        # Shouldn't get anything as a user
        $ret = $this->call('dashboard', 'GET', []);
        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        assertFalse(array_key_exists('messagehistory', $dash));

        # But should as an admin
        $u->setPrivate('systemrole', User::ROLE_ADMIN);
        $ret = $this->call('dashboard', 'GET', []);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        error_log("Dash " . var_export($dash, true));
        assertGreaterThan(0, $dash['messagehistory']);

        error_log(__METHOD__ . " end");
    }
}

