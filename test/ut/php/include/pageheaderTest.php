<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class pageheaderTest extends IznikTest {
    protected function setUp() {
        parent::setUp ();
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $this->expectOutputRegex('/.*<meta property="og:title" content="' . SITE_NAME . '"\/>./*');
        $this->expectOutputRegex('/.*<script.*/');
        require_once(IZNIK_BASE . '/include/pageheader.php');

        error_log(__METHOD__ . " end");
    }
}

