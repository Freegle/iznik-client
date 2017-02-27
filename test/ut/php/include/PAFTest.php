<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/misc/PAF.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class PAFTest extends IznikTestCase {
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

    public function testLoad() {
        error_log(__METHOD__);

        if (file_exists('/tmp/ut_paf0000000000.csv')) {
            unlink('/tmp/ut_paf0000000000.csv');
        }

        $p = new PAF($this->dbhr, $this->dbhm);
        $p->load(UT_DIR . '/php/misc/pc.csv', '/tmp/ut_paf');

        $csv = file_get_contents('/tmp/ut_paf0000000000.csv');
        error_log("CSV is $csv");
    }

    public function testList() {
        error_log(__METHOD__);
        $p = new PAF($this->dbhr, $this->dbhm);

        $ids = $p->listForPostcode('AB10 1AA');
        $line = $p->getSingleLine($ids[0]);
        error_log($line);
        self::assertEquals("Resources Management, St. Nicholas House Broad Street, ABERDEEN AB10 1AA", $line);

        error_log(__METHOD__ . " end");
    }
}

