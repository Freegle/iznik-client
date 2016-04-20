<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class apiTest extends IznikAPITestCase {
    public function testBadCall() {
        error_log(__METHOD__);

        $ret = $this->call('unknown', 'GET', []);
        assertEquals(1000, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testDuplicatePOST() {
        error_log(__METHOD__);

        # We prevent duplicate posts within a short time.
        error_log("POST - should work");
        $ret = $this->call('test', 'POST', []);
        assertEquals(1000, $ret['ret']);

        error_log("POST - should fail");
        $ret = $this->call('test', 'POST', []);
        assertEquals(999, $ret['ret']);

        sleep(DUPLICATE_POST_PROTECTION + 1);
        error_log("POST - should work");
        $ret = $this->call('test', 'POST', []);
        assertEquals(1000, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testException() {
        error_log(__METHOD__);

        $ret = $this->call('exception', 'POST', []);
        assertEquals(998, $ret['ret']);

        # Should fail a couple of times and then work.
        $ret = $this->call('DBexceptionWork', 'POST', []);
        assertEquals(1000, $ret['ret']);

        # Should fail.
        $ret = $this->call('DBexceptionFail', 'POST', []);
        assertEquals(997, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testLeaveTrans() {
        error_log(__METHOD__);

        # Should fail a couple of times and then work.
        $ret = $this->call('DBleaveTrans', 'POST', []);
        assertEquals(1000, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testOptions() {
        error_log(__METHOD__);

        # Testing header output is hard
        # TODO ...but doable, apparently.
        $ret = $this->call('test', 'OPTIONS', []);

        error_log(__METHOD__ . " end");
    }

    public function testOverride() {
        error_log(__METHOD__);

        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'get';
        $ret = $this->call('echo', 'GET', []);
        unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        assertEquals('get', $ret['type']);

        error_log(__METHOD__ . " end");
    }

    public function testModel() {
        error_log(__METHOD__);

        $ret = $this->call('wibble', 'GET', [
            'model' => json_encode([
                'call' => 'echo'
            ])
        ]);

        error_log(var_export($ret, true));

        assertEquals('echo', $ret['call']);

        error_log(__METHOD__ . " end");
    }
}

