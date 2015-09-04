<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/misc/scripts.php';
require_once(IZNIK_BASE . "/lib/JSMin.php");

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class scriptsTest extends IznikTest {
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

        # Don't minimise - should return lots of lines.
        $ret = scriptInclude(FALSE);
        assertGreaterThan(5, count($ret[1]));

        # Now minimised.
        $ret = scriptInclude(function($str) { return(JSMin::minify($str)); });
        assertEquals(2, count($ret[1]));

        # Remove cache file
        unlink($ret[0]);
        $ret = scriptInclude(function($str) { return(JSMin::minify($str)); });
        assertEquals(2, count($ret[1]));

        # Touch a file and do it again - should reminimise to a different file.
        touch(IZNIK_BASE . "/http/js/iznik/router.js");
        $ret2 = scriptInclude(function($str) { return(JSMin::minify($str)); });
        assertNotEquals($ret, $ret2);

        # Return an exception from the minification.  Should still work.
        error_log("Now exception");
        touch(IZNIK_BASE . "/http/js/iznik/router.js");
        $ret = scriptInclude(function($str) { throw new Exception(); });
        assertEquals(2, count($ret[1]));

        error_log(__METHOD__ . " end");
    }
}

