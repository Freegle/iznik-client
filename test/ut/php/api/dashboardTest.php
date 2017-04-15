<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class dashboardTest extends IznikAPITestCase {
    public function testLoggedOut() {
        error_log(__METHOD__);

        $ret = $this->call('session', 'GET', []);
        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testAdmin() {
        error_log(__METHOD__);

        # Now log in
        $u = User::get($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $u = User::get($this->dbhr, $this->dbhm, $id);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        error_log("After login {$_SESSION['id']}");

        # Shouldn't get anything as a user
        $ret = $this->call('dashboard', 'GET', []);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        assertFalse(array_key_exists('messagehistory', $dash));

        # But should as an admin
        $u->setPrivate('systemrole', User::SYSTEMROLE_ADMIN);
        $ret = $this->call('dashboard', 'GET', [
            'systemwide' => true
        ]);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        assertGreaterThan(0, $dash['ApprovedMessageCount']);

        error_log(__METHOD__ . " end");
    }

    public function testGroups() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $id1 = $u->create('Test', 'User', NULL);
        $id2 = $u->create('Test', 'User', NULL);
        $u1 = User::get($this->dbhr, $this->dbhm, $id1);
        $u2 = User::get($this->dbhr, $this->dbhm, $id2);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_OTHER);
        $group2 = $g->create('testgroup2', Group::GROUP_OTHER);
        $u1->addMembership($group1);
        $u1->addMembership($group2, User::ROLE_MODERATOR);
        $u2->addMembership($group2, User::ROLE_MODERATOR);

        # Shouldn't get anything as a user
        $ret = $this->call('dashboard', 'GET', [
            'group' => $group1
        ]);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        assertFalse(array_key_exists('messagehistory', $dash));

        # But should as a mod
        $ret = $this->call('dashboard', 'GET', [
            'group' => $group2
        ]);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        assertGreaterThan(0, $dash['ApprovedMessageCount']);

        # And also if we ask for our groups
        $ret = $this->call('dashboard', 'GET', [
            'allgroups' => TRUE,
            'grouptype' => 'Other'
        ]);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        assertGreaterThan(0, $dash['ApprovedMessageCount']);

        # ...but not if we ask for the wrong type
        $ret = $this->call('dashboard', 'GET', [
            'allgroups' => TRUE,
            'grouptype' => 'Freegle'
        ]);
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        error_log(var_export($dash, TRUE));
        assertEquals(0, count($dash['ApprovedMessageCount']));

        error_log(__METHOD__ . " end");
    }

    public function testArea() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup1', Group::GROUP_OTHER);
        $g->setPrivate('polyofficial', 'POLYGON((179.21 8.53, 179.21 8.54, 179.22 8.54, 179.22 8.53, 179.21 8.53, 179.21 8.53))');

        $this->dbhm->preExec("REPLACE INTO authorities (name, polygon) VALUES ('Tuvulu Authority', GeomFromText('POLYGON((179.2 8.5, 179.2 8.6, 179.3 8.6, 179.3 8.5, 179.2 8.5))'))");

        $ret = $this->call('dashboard', 'GET', [
            'area' => 'Tuvulu Authority'
        ]);
        error_log("Returned " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        $dash = $ret['dashboard'];
        self::assertEquals($group1, $ret['dashboard']['groupids'][0]);

        error_log(__METHOD__ . " end");
    }
}

