<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/newsfeed/Newsfeed.php';
require_once IZNIK_BASE . '/include/misc/Location.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class newsfeedAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhm;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");

        $l = new Location($this->dbhr, $this->dbhm);
        $this->areaid = $l->create(NULL, 'Tuvalu Central', 'Polygon', 'POLYGON((179.21 8.53, 179.21 8.54, 179.22 8.54, 179.22 8.53, 179.21 8.53, 179.21 8.53))', 0);
        assertNotNull($this->areaid);
        $this->pcid = $l->create(NULL, 'TV13', 'Postcode', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');
        $this->fullpcid = $l->create(NULL, 'TV13 1HH', 'Postcode', 'POINT(179.2167 8.53333)', 0);

        $this->user = User::get($this->dbhr, $this->dbhm);
        $this->uid = $this->user->create(NULL, NULL, 'Test User');
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $this->user->setPrivate('lastlocation', $this->fullpcid);
    }

    protected function tearDown() {
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $this->dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $this->dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        # Logged out.
        error_log("Logged out");
        $ret = $this->call('newsfeed', 'GET', []);
        assertEquals(1, $ret['ret']);

        # Logged in - empty
        error_log("Logged in - empty");
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('newsfeed', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['ret']['newsfeed']));
        assertEquals(0, count($ret['ret']['users']));

        # Post something.
        error_log("Post something");
        $ret = $this->call('newsfeed', 'POST', [
            'message' => 'Test'
        ]);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        error_log("Created feed {$ret['id']}");
        $nid = $ret['id'];

        # Get this individual one
        $ret = $this->call('newsfeed', 'GET', [
            'id' => $nid
        ]);
        assertEquals(0, $ret['ret']);
        self::assertEquals($nid, $ret['newsfeed']['id']);

        # Hack it to have a message for coverage
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.4);

        $m = new Message($this->dbhr, $this->dbhm);

        $msg = $this->unique(file_get_contents(IZNIK_BASE . '/test/ut/php/msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test item (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'testgroup1@yahoogroups.com', $msg);
        list($mid, $already) = $m->save();
        $this->dbhm->preExec("UPDATE newsfeed SET msgid = ? WHERE id = ?;", [
            $mid,
            $nid
        ]);

        error_log("Logged in - one item");
        $ret = $this->call('newsfeed', 'GET', []);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['newsfeed']));
        self::assertEquals('Test', $ret['newsfeed'][0]['message']);
        assertEquals(1, count($ret['users']));
        self::assertEquals($this->uid, array_pop($ret['users'])['id']);
        self::assertEquals($mid, $ret['newsfeed'][0]['refmsg']['id']);

        # Reply
        $ret = $this->call('newsfeed', 'POST', [
            'message' => 'Test',
            'replyto' => $nid
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('newsfeed', 'GET', []);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['newsfeed']));
        assertEquals(1, count($ret['newsfeed'][0]['replies']));

        error_log(__METHOD__ . " end");
    }
}

