<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/CommunityEvent.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class communityEventAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhm;
        $this->dbhm = $dbhm;

        $g = new Group($this->dbhr, $this->dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_REUSE);
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addMembership($this->groupid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid2 = $u->create(NULL, NULL, 'Test User');
        $this->user2 = new User($this->dbhr, $this->dbhm, $this->uid2);
        $this->user2->addMembership($this->groupid, User::ROLE_MODERATOR);
        assertGreaterThan(0, $this->user2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $dbhm->preExec("DELETE FROM communityevents WHERE title = 'Test event' OR title = 'UTTest';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testCreate() {
        error_log(__METHOD__);

        # Get invalid id
        $ret = $this->call('communityevent', 'GET', [
            'id' => -1
        ]);
        assertEquals(2, $ret['ret']);

        # Create when not logged in
        $ret = $this->call('communityevent', 'POST', [
            'title' => 'UTTest'
        ]);
        assertEquals(1, $ret['ret']);

        # Create without mandatories
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('communityevent', 'POST', [
        ]);
        assertEquals(2, $ret['ret']);

        # Create as logged in user.
        $ret = $this->call('communityevent', 'POST', [
            'title' => 'UTTest',
            'location' => 'UTTest',
            'description' => 'UTTest'
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];
        assertNotNull($id);
        error_log("Created event $id");

        # Add group
        $ret = $this->call('communityevent', 'PATCH', [
            'id' => $id,
            'groupid' => $this->groupid,
            'action' => 'AddGroup'
        ]);
        assertEquals(0, $ret['ret']);

        # Add date
        $ret = $this->call('communityevent', 'PATCH', [
            'id' => $id,
            'start' => ISODate('@' . strtotime('next wednesday 2pm')),
            'end' => ISODate('@' . strtotime('next wednesday 4pm')),
            'action' => 'AddDate'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('communityevent', 'GET', [
            'pending' => true
        ]);
        error_log("Result of get all " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['communityevents']));
        assertEquals($id, $ret['communityevents'][0]['id']);

        # Log in as the mod
        assertTrue($this->user2->login('testpw'));

        # Edit it
        $ret = $this->call('communityevent', 'PATCH', [
            'id' => $id,
            'title' => 'UTTest2'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('communityevent', 'GET', [
            'id' => $id
        ]);
        assertEquals('UTTest2', $ret['communityevent']['title']);

        # Edit it
        $ret = $this->call('communityevent', 'PUT', [
            'id' => $id,
            'title' => 'UTTest3'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('communityevent', 'GET', [
            'id' => $id
        ]);
        assertEquals('UTTest3', $ret['communityevent']['title']);

        $dateid = $ret['communityevent']['dates'][0]['id'];

        # And back as the user
        assertTrue($this->user->login('testpw'));

        $ret = $this->call('communityevent', 'PATCH', [
            'id' => $id,
            'groupid' => $this->groupid,
            'action' => 'RemoveGroup'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('communityevent', 'PATCH', [
            'id' => $id,
            'dateid' => $dateid,
            'action' => 'RemoveDate'
        ]);
        assertEquals(0, $ret['ret']);
        
        $ret = $this->call('communityevent', 'DELETE', [
            'id' => $id
        ]);

        error_log(__METHOD__ . " end");
    }
}

