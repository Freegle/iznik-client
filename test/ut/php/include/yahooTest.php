<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/yahoo.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class yahooTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $y = new Yahoo($this->dbhr, $this->dbhm);
        $rc  = $y->login();
        assertEquals(1, $rc[1]['ret']);
        assertTrue(array_key_exists('redirect', $rc[1]));

        $mock = $this->getMockBuilder('LightOpenID')
            ->disableOriginalConstructor()
            ->setMethods(array('validate'))
            ->getMock();
        $mock->method('validate')->willReturn(true);
        $y->setOpenid($mock);
        $rc  = $y->login();
        assertNull($rc);

        error_log(__METHOD__ . " end");
    }
}

