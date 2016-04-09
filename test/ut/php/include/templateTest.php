<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/misc/template.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class templateTest extends IznikTestCase {
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

        $ret = addTemplate(IZNIK_BASE . '/http/template/', IZNIK_BASE . '/http/template/');
        error_log("Templates " . var_export($ret, TRUE));
        $found = FALSE;
        foreach ($ret as $key => $tpl) {
            if (strpos($tpl, 'layout_layout') !== FALSE) {
                $found = TRUE;
            }
        }
        assertTrue($found);

        error_log(__METHOD__ . " end");
    }
}

