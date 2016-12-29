<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/misc/Polls.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class pollAPITest extends IznikAPITestCase {
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp() {
        parent::setUp ();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhm;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM polls WHERE name LIKE 'UTTest%';");
    }

    protected function tearDown() {
        $this->dbhm->preExec("DELETE FROM polls WHERE name LIKE 'UTTest%';");
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $c = new Polls($this->dbhr, $this->dbhm);
        $id = $c->create('UTTest', 1, 'Test');

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        # Get invalid id
        $ret = $this->call('poll', 'GET', [
            'id' => -1
        ]);
        assertEquals(2, $ret['ret']);

        # Get valid id
        $ret = $this->call('poll', 'GET', [
            'id' => $id
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['poll']['id']);

        # Get for user
        $ret = $this->call('poll', 'GET', [
            'id' => $id
        ]);

        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['poll']['id']);

        # Shown
        $ret = $this->call('poll', 'POST', [
            'id' => $id,
            'shown' => true
        ]);
        assertEquals(0, $ret['ret']);

        # Response
        $ret = $this->call('poll', 'POST', [
            'id' => $id,
            'response' => [
                'test' => 'response'
            ]
        ]);
        assertEquals(0, $ret['ret']);

        # Get - shouldn't return this one.
        error_log("Shouldn't return this one");
        $ret = $this->call('poll', 'GET', []);

        assertEquals(0, $ret['ret']);
        if (array_key_exists('poll', $ret)) {
            self::assertNotEquals($id, $ret['poll']['id']);
        }

        error_log(__METHOD__ . " end");
    }

    public function testLogin() {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        # Fake FB login.
        assertGreaterThan(0, $u->addLogin(User::LOGIN_FACEBOOK, NULL, 'testpw'));
        $logins = $u->getLogins();
        error_log("Got logins " . var_export($logins, TRUE));

        # Create a poll requiring FB.
        $c = new Polls($this->dbhr, $this->dbhm);
        $id = $c->create('UTTest', 1, 'Test', User::LOGIN_FACEBOOK);
        $found = FALSE;

        do {
            # Get for user until we run out or find it.
            $ret = $this->call('poll', 'GET', []);

            assertEquals(0, $ret['ret']);
            assertNotNull($ret['poll']['id']);

            if ($id == $ret['poll']['id']) {
                $found = TRUE;
            }

            # Shown
            error_log("Mark $id as shown");
            $ret = $this->call('poll', 'POST', [
                'id' => $id,
                'response' => [
                    'test' => true
                ]
            ]);
            assertEquals(0, $ret['ret']);
        } while (!$found);

        assertTrue($found);

        error_log(__METHOD__ . " end");
    }
}

