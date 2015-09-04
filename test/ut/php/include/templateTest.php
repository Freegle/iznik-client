<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/misc/template.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class templateTest extends IznikTest {
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

        $widget_location = IZNIK_BASE . '/http/template/';
        $collection = dirToArray($widget_location);
        $ret = addTemplate($collection, $widget_location);
        error_log(var_export($ret, true));
        assertTrue(strpos($ret[1], 'layout') !== FALSE);

        error_log(__METHOD__ . " end");
    }
}

