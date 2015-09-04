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
class apiTest extends IznikTest {
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

    public function testBasic() {
        error_log(__METHOD__);

        # This has to run from the API directory, as it would on the web server
        chdir(IZNIK_BASE . '/http/api');

        $this->expectOutputRegex('/.*No return code defined.*/');

        require_once(IZNIK_BASE . '/http/api/api.php');

        error_log(__METHOD__ . " end");
    }
}

