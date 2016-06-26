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
class userSearchAPITest extends IznikAPITestCase {
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

    public function testCreateDelete() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $s = new UserSearch($this->dbhr, $this->dbhm);
        $id = $s->create($this->uid, NULL, 'testsearch');
        
        $ret = $this->call('usersearch', 'GET', []);
        assertEquals(1, $ret['ret']);

        assertTrue($this->user->login('testpw'));

        $ret = $this->call('usersearch', 'GET', []);
        error_log(var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['usersearches']));
        assertEquals($id, $ret['usersearches'][0]['id']);

        $ret = $this->call('usersearch', 'GET', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['usersearch']['id']);

        $ret = $this->call('usersearch', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('usersearch', 'GET', []);
        assertEquals(0, $ret['ret']);
        assertEquals(0, count($ret['usersearches']));

        $s->delete();

        error_log(__METHOD__ . " end");
    }
}

