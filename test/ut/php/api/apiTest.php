<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITest.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class apiTest extends IznikAPITest {
    public function testBadCall() {
        error_log(__METHOD__);

        $ret = $this->call([]);
        assertEquals(1000, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testDuplicatePOST() {
        error_log(__METHOD__);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = 'test';
        $_REQUEST = [
            'test' => 'dup'
        ];

        # We prevent duplicate posts within a short time.
        error_log("POST - should work");
        $ret = $this->call([]);
        assertEquals(1000, $ret['ret']);

        error_log("POST - should fail");
        $ret = $this->call([]);
        assertEquals(999, $ret['ret']);

        sleep(DUPLICATE_POST_PROTECTION + 1);
        error_log("POST - should work");
        $ret = $this->call([]);
        assertEquals(1000, $ret['ret']);

        error_log(__METHOD__ . " end");
    }

    public function testException() {
        error_log(__METHOD__);

        $ret = $this->call([
            'call' => 'exception'
        ]);
        assertEquals(998, $ret['ret']);

        # Should fail a couple of times and then work.
        $ret = $this->call([
            'call' => 'DBexceptionWork'
        ]);
        assertEquals(1000, $ret['ret']);

        # Should fail.
        $ret = $this->call([
            'call' => 'DBexceptionFail'
        ]);
        assertEquals(997, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}

