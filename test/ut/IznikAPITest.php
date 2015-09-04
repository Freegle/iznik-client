<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/http/api/session_get.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class IznikAPITest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
        parent::tearDown ();

        @session_destroy();
    }

    public function __construct() {
    }

    public function call($params) {
        $_REQUEST = array_merge($_REQUEST, $params);

        # API calls have to run from the api directory, as they would from the web server.
        chdir(IZNIK_BASE . '/http/api');
        require_once(IZNIK_BASE . '/http/api/api.php');

        $ret = json_decode($this->getActualOutput(), true);
        return($ret);
    }
}

