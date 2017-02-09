<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Story.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class addressAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'TV13%';");
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
        $ret = $this->call('address', 'PUT', [
            'line1' => 'Test'
        ]);
        assertEquals(1, $ret['ret']);

        # Create logged in
        $l = new Location($this->dbhr, $this->dbhm);
        $pcid = $l->create(NULL, 'TV13', 'Postcode', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');

        assertTrue($this->user->login('testpw'));
        $ret = $this->call('address', 'PUT', [
            'line1' => 'Test',
            'postcodeid' => $pcid
        ]);
        assertEquals(0, $ret['ret']);

        $id = $ret['id'];
        assertNotNull($id);

        # Get with id - should work
        $ret = $this->call('address', 'GET', [ 'id' => $id ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['address']['id']);
        assertEquals('Test', $ret['address']['line1']);

        # List
        $ret = $this->call('address', 'GET', []);
        assertEquals(0, $ret['ret']);
        self::assertEquals(1, count($ret['addresses']));
        assertEquals($id, $ret['addresses'][0]['id']);
        assertEquals('Test', $ret['addresses'][0]['line1']);

        # Edit
        $ret = $this->call('address', 'PATCH', [
            'id' => $id,
            'line1' => 'Test2'
        ]);
        assertEquals(0, $ret['ret']);
        $ret = $this->call('address', 'GET', [ 'id' => $id ]);
        assertEquals(0, $ret['ret']);
        assertEquals('Test2', $ret['address']['line1']);

        # Delete
        $ret = $this->call('address', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

