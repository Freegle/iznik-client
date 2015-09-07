<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/user/User.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class IznikAPITest extends IznikTest {
    public $dbhr, $dbhm;

    private $lastOutput = NULL;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $_SESSION['id'] = NULL;

        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_emails WHERE email IN ('test@test.com', 'test2@test.com'));");
        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_logins WHERE uid IN ('testid', '1234'));");
    }

    protected function tearDown() {
        parent::tearDown ();

        @session_destroy();
    }

    public function __construct() {
    }

    public function call($call, $type, $params) {
        $_REQUEST = array_merge($params);

        $_SERVER['REQUEST_METHOD'] = $type;
        $_SERVER['REQUEST_URI'] = "/api/$call.php";
        $_REQUEST['call'] = $call;

        # API calls have to run from the api directory, as they would from the web server.
        chdir(IZNIK_BASE . '/http/api');
        require(IZNIK_BASE . '/http/api/api.php');

        # Get the output since we last did this.
        $op = $this->getActualOutput();

        if ($this->lastOutput) {
            $len = strlen($this->lastOutput);
            $this->lastOutput = $op;
            $op = substr($op, $len);
        } else {
            $this->lastOutput = $op;
        }

        $ret = json_decode($op, true);

        return($ret);
    }
}

