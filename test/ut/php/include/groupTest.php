<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once BASE_DIR . '/include/group/Group.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class groupTest extends IznikTest {
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

        $g = new Group($this->dbhr, $this->dbhm);
        $g->create('testgroup', Group::GROUP_REUSE);
        $atts = $g->getPublic();
        assertEquals('testgroup', $atts['nameshort']);
        assertEquals($atts['id'], $g->getPrivate('id'));
        assertNull($g->getPrivate('invalidid'));
        assertGreaterThan(0 ,$g->delete());

        error_log(__METHOD__ . " end");
    }
}

