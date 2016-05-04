<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/..');
}
require_once UT_DIR . '/IznikWebTestCase.php';

class homeTest extends IznikWebTestCase
{
    public function testBasic()
    {
        error_log(__METHOD__);

        $this->driver->get(USER_TEST_SITE);
        $this->waitLoad();

        error_log(__METHOD__ . " end");
    }
}
