<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once(UT_DIR . '/../../include/config.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/user/User.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class sessionClassTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE firstname = 'Test' AND lastname = 'User';");
        $_SESSION['id'] = NULL;
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        # Logged out
        $me = whoAmI($this->dbhm, $this->dbhm);
        assertNull($me);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);

        $s = new Session($this->dbhm, $this->dbhm);
        $ret = $s->create($id);

        # Verify it
        $ver = $s->verify($ret['id'], $ret['series'], $ret['token']);
        assertEquals($id, $ver);

        $_SESSION['id'] = NULL;

        assertNull($s->verify($id, $ret['series'] . 'z', $ret['token']));

        $me = whoAmI($this->dbhm, $this->dbhm);
        assertNull($me);

        # Now fake the login
        $_SESSION['id'] = $id;
        $me = whoAmI($this->dbhm, $this->dbhm);
        assertEquals($id, $me->getPrivate('id'));

        error_log(__METHOD__ . " end");
    }

    public function testMisc() {
        error_log(__METHOD__);

        # Can call this twice
        prepareSession($this->dbhm, $this->dbhm);
        prepareSession($this->dbhm, $this->dbhm);

        session_reopen();

        error_log(__METHOD__ . " end");
    }

    public function testCookie() {
        error_log(__METHOD__);

        $u = User::get($this->dbhm, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);

        $s = new Session($this->dbhm, $this->dbhm);
        $ret = $s->create($id);

        # Cookie should log us in
        $_SESSION['id'] = NULL;
        $_REQUEST['persistent'] = $ret;
        global $sessionPrepared;
        $sessionPrepared = FALSE;
        prepareSession($this->dbhm, $this->dbhm);
        assertTrue($_SESSION['logged_in']);
        assertEquals($id, $_SESSION['id']);

        # ...repeatedly
        $_SESSION['id'] = NULL;
        $_REQUEST['persistent'] = $ret;
        global $sessionPrepared;
        $sessionPrepared = FALSE;
        prepareSession($this->dbhm, $this->dbhm);
        assertTrue($_SESSION['logged_in']);
        assertEquals($id, $_SESSION['id']);

        # But not if the session has gone.
        $s->destroy($id, NULL);
        $_SESSION['logged_in'] = FALSE;
        prepareSession($this->dbhm, $this->dbhm);
        assertFalse($_SESSION['logged_in']);

        error_log(__METHOD__ . " end");
    }}

