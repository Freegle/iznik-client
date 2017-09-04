<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Schedule.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class scheduleAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $uid2 = $u->create(NULL, NULL, 'Test User');
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        # Create logged out - should fail
        $ret = $this->call('schedule', 'POST', [
            'userid' => $uid2,
            'schedule' => [
                'test' => 1
            ]
        ]);
        assertEquals(1, $ret['ret']);

        # Create logged in - should work
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('schedule', 'POST', [
            'userid' => $uid2,
            'schedule' => [
                'test' => 1
            ]
        ]);
        assertEquals(0, $ret['ret']);

        $id = $ret['id'];
        assertNotNull($id);

        # Get with id - should work
        $ret = $this->call('schedule', 'GET', [ 'id' => $id ]);
        error_log("Returned " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['schedule']['id']);
        self::assertEquals([
            'test' => 1
        ], $ret['schedule']['schedule']);
        assertTrue(in_array($this->uid, $ret['schedule']['users']));
        assertTrue(in_array($uid2, $ret['schedule']['users']));

        # Get without id
        $ret = $this->call('schedule', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['schedules']));
        assertEquals($id, $ret['schedules'][0]['id']);

        # Edit
        $ret = $this->call('schedule', 'PATCH', [
            'id' => $id,
            'schedule' => [
                'test' => 2
            ]
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('schedule', 'GET', [ 'id' => $id ]);
        self::assertEquals([
            'test' => 2
        ], $ret['schedule']['schedule']);

        error_log(__METHOD__ . " end");
    }
}

