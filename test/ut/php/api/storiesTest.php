<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Search.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class storiesAPITest extends IznikAPITestCase {
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
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        # Create logged out - should fail
        $ret = $this->call('stories', 'PUT', [
            'headline' => 'Test',
            'story' => 'Test'
        ]);
        assertEquals(1, $ret['ret']);

        # Create logged in - should work
        assertTrue($this->user->login('testpw'));
        $ret = $this->call('stories', 'PUT', [
            'headline' => 'Test',
            'story' => 'Test'
        ]);
        assertEquals(0, $ret['ret']);

        $id = $ret['id'];
        assertNotNull($id);

        # Get without id - should fail
        $ret = $this->call('stories', 'GET', []);
        assertEquals(3, $ret['ret']);

        # Get with id - should work
        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['story']['id']);
        self::assertEquals('Test', $ret['story']['headline']);
        self::assertEquals('Test', $ret['story']['story']);

        # Edit
        $ret = $this->call('stories', 'PATCH', [
            'id' => $id,
            'headline' => 'Test2',
            'story' => 'Test2',
            'public' => 0
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        self::assertEquals('Test2', $ret['story']['headline']);
        self::assertEquals('Test2', $ret['story']['story']);

        $_SESSION['id'] = NULL;

        # Get logged out - should fail, not public
        $ret = $this->call('stories', 'GET', [ 'id' => $id ]);
        assertEquals(2, $ret['ret']);

        # Delete - should fail
        $ret = $this->call('stories', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(2, $ret['ret']);

        assertTrue($this->user->login('testpw'));

        # Delete - should work
        $ret = $this->call('stories', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        # Delete - fail
        $ret = $this->call('stories', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(2, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

