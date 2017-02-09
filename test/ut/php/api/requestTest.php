<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Request.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class requestAPITest extends IznikAPITestCase {
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
        $ret = $this->call('request', 'PUT', [
            'reqtype' => Request::TYPE_BUSINESS_CARDS
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

        $aid = $ret['id'];
        assertNotNull($aid);

        $ret = $this->call('request', 'PUT', [
            'reqtype' => Request::TYPE_BUSINESS_CARDS,
            'addressid' => $aid
        ]);
        assertEquals(0, $ret['ret']);
        $id = $ret['id'];

        # Get with id - should work
        $ret = $this->call('request', 'GET', [ 'id' => $id ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['request']['id']);
        assertEquals(Request::TYPE_BUSINESS_CARDS, $ret['request']['type']);
        self::assertEquals($aid, $ret['request']['address']['id']);

        # List
        $ret = $this->call('request', 'GET', []);
        error_log("List " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        self::assertEquals(1, count($ret['requests']));
        assertEquals($aid, $ret['requests'][0]['address']['id']);

        # Delete
        $ret = $this->call('request', 'DELETE', [
            'id' => $id
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

