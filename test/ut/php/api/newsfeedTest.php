<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/newsfeed/Newsfeed.php';
require_once IZNIK_BASE . '/include/misc/Location.php';
require_once IZNIK_BASE . '/include/group/CommunityEvent.php';
require_once IZNIK_BASE . '/include/group/Volunteering.php';
require_once IZNIK_BASE . '/include/group/Facebook.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class newsfeedAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;
    private $msgsSent = [];

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

        $this->user2 = User::get($this->dbhr, $this->dbhm);
        $this->uid2 = $this->user2->create(NULL, NULL, 'Test User');
        assertGreaterThan(0, $this->user2->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        $this->user2->setPrivate('lastlocation', $this->fullpcid);
        $this->user2->addEmail('test@test.com');
    }

    protected function tearDown() {
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $this->dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $this->dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function sendMock($mailer, $message) {
        $this->msgsSent[] = $message->toString();
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
        error_log("Post something as {$this->uid}");
        $ret = $this->call('newsfeed', 'POST', [
            'message' => 'Test with url https://google.co.uk'
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
        self::assertEquals('Google', $ret['newsfeed']['preview']['title']);

        # Should mail out to the other user.
        $n = $this->getMockBuilder('Newsfeed')
            ->setConstructorArgs(array($this->dbhm, $this->dbhm))
            ->setMethods(array('sendIt'))
            ->getMock();

        $n->method('sendIt')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        assertEquals(1, $n->digest($this->uid2));
        assertEquals(0, $n->digest($this->uid2));

        # Hack it to have a message for coverage
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.5);

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
        $ret = $this->call('newsfeed', 'GET', [
            'types' => [
                Newsfeed::TYPE_MESSAGE
            ]
        ]);
        error_log("Returned " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['newsfeed']));
        self::assertEquals('Test with url https://google.co.uk', $ret['newsfeed'][0]['message']);
        assertEquals(1, count($ret['users']));
        self::assertEquals($this->uid, array_pop($ret['users'])['id']);
        self::assertEquals($mid, $ret['newsfeed'][0]['refmsg']['id']);

        # Like
        assertTrue($this->user2->login('testpw'));
        $ret = $this->call('newsfeed', 'POST', [
            'id' => $nid,
            'action' => 'Love'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('newsfeed', 'GET', [
            'id' => $nid
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        self::assertEquals(1, $ret['newsfeed']['loves']);
        self::assertTrue($ret['newsfeed']['loved']);

        # Will have generated a notification
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('notification', 'GET', [
            'count' => TRUE
        ]);
        assertEquals(0, $ret['ret']);
        self::assertEquals(1, $ret['count']);

        $ret = $this->call('notification', 'GET', []);
        error_log("Notifications " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        self::assertEquals(1, count($ret['notifications']));
        self::assertEquals($this->uid2, $ret['notifications'][0]['fromuser']['id']);
        $notifid = $ret['notifications'][0]['id'];

        # Mark it as seen
        $ret = $this->call('notification', 'POST', [
            'id' => $notifid,
            'action' => 'Seen'
        ]);
        assertEquals(0, $ret['ret']);

        assertTrue($this->user2->login('testpw'));
        $ret = $this->call('newsfeed', 'POST', [
            'id' => $nid,
            'action' => 'Seen'
        ]);
        assertEquals(0, $ret['ret']);

        assertTrue($this->user2->login('testpw'));
        $ret = $this->call('newsfeed', 'POST', [
            'id' => $nid,
            'action' => 'Unlove'
        ]);
        assertEquals(0, $ret['ret']);

        assertTrue($this->user->login('testpw'));
        $ret = $this->call('newsfeed', 'GET', [
            'count' => TRUE
        ]);
        assertEquals(0, $ret['ret']);

        assertTrue($this->user->login('testpw'));
        $ret = $this->call('newsfeed', 'GET', [
            'id' => $nid
        ]);
        assertEquals(0, $ret['ret']);
        self::assertEquals(0, $ret['newsfeed']['loves']);
        self::assertFalse($ret['newsfeed']['loved']);

        # Reply
        $ret = $this->call('newsfeed', 'POST', [
            'message' => 'Test',
            'replyto' => $nid
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('newsfeed', 'GET', [
            'types' => [
                Newsfeed::TYPE_MESSAGE
            ]
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['newsfeed']));
        assertEquals(1, count($ret['newsfeed'][0]['replies']));

        # Refer it to WANTED - generates another reply.
        $ret = $this->call('newsfeed', 'POST', [
            'id' => $nid,
            'action' => 'ReferToWanted'
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('newsfeed', 'GET', [
            'id' => $nid
        ]);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(2, count($ret['newsfeed']['replies']));

        # Report it
        $ret = $this->call('newsfeed', 'POST', [
            'id' => $nid,
            'action' => 'Report',
            'reason' => "Test"
        ]);
        assertEquals(0, $ret['ret']);

        # Delete it
        $this->user->addMembership($gid, User::ROLE_MODERATOR);

        $ret = $this->call('newsfeed', 'DELETE', [
            'id' => $nid
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testCommunityEvent() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        # Create an event - should result in a newsfeed item
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.5);

        $e = new CommunityEvent($this->dbhr, $this->dbhm);
        $eid = $e->create($this->uid, 'Test event', 'Test location', NULL, NULL, NULL, NULL, NULL);
        $e->addGroup($gid);
        $e->setPrivate('pending', 0);

        $ret = $this->call('newsfeed', 'GET', [
            'types' => [
                Newsfeed::TYPE_COMMUNITY_EVENT
            ]
        ]);

        error_log("Feed " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['newsfeed']));
        self::assertEquals('Test event', $ret['newsfeed'][0]['communityevent']['title']);

        error_log(__METHOD__ . " end");
    }

    public function testVolunteering() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.5);

        $e = new Volunteering($this->dbhr, $this->dbhm);
        $eid = $e->create($this->uid, 'Test opp', FALSE, 'Test location', NULL, NULL, NULL, NULL, NULL, NULL);
        $e->addGroup($gid);
        $e->setPrivate('pending', 0);

        $ret = $this->call('newsfeed', 'GET', [
            'types' => [
                Newsfeed::TYPE_VOLUNTEER_OPPORTUNITY
            ]
        ]);

        error_log("Feed " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['newsfeed']));
        self::assertEquals('Test opp', $ret['newsfeed'][0]['volunteering']['title']);

        error_log(__METHOD__ . " end");
    }

    public function testPublicity() {
        error_log(__METHOD__);

        assertTrue($this->user->login('testpw'));

        # Find a publicity post so that we can issue the API call from that point.
        $posts = $this->dbhr->preQuery("SELECT id, timestamp FROM newsfeed WHERE `type` = ? ORDER BY timestamp DESC LIMIT 1;", [
            Newsfeed::TYPE_CENTRAL_PUBLICITY
        ]);

        self::assertEquals(1, count($posts));
        $time = strtotime($posts[0]['timestamp']);
        $time++;
        $newtime = ISODate('@' . $time);
        error_log("{$posts[0]['timestamp']} => $newtime");

        $ctx = [
            'distance' => 0,
            'timestamp' => $newtime
        ];

        $ret = $this->call('newsfeed', 'GET', [
            'context' => $ctx,
            'types' => [
                Newsfeed::TYPE_CENTRAL_PUBLICITY
            ]
        ]);

        error_log("Feed " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertGreaterThan(1, count($ret['newsfeed']));
        self::assertEquals(Newsfeed::TYPE_CENTRAL_PUBLICITY, $ret['newsfeed'][0]['type']);
        assertNotFalse(pres('postid', $ret['newsfeed'][0]['publicity']));

        error_log(__METHOD__ . " end");
    }
}

