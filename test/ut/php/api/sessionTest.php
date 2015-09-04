<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class sessionTest extends IznikAPITest {
    public function testBasic() {
        error_log(__METHOD__);

        $ret = $this->call(array(
            'call' => 'session_get'
        ));

        assertEquals(1, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

