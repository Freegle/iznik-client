<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/misc/Image.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class imageTest extends IznikTestCase {
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

    public function testNullParams() {
        error_log(__METHOD__);

        $data = file_get_contents('images/Tile.jpg');
        $i = new Image($data);

        $w = $i->width();
        $h = $i->height();

        $i->scale(NULL, NULL);

        assertEquals($w, $i->width());
        assertEquals($h, $i->height());

        error_log(__METHOD__ . " end");
    }
}

