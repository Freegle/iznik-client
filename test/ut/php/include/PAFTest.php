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

        global $dbconfig;
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willReturn(TRUE);

        $l = new Location($this->dbhr, $mock);
        $pcid = $l->create(NULL, 'TV10 1AA', 'Postcode', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');
        $pcid = $l->create(NULL, 'TV10 1AB', 'Postcode', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');
        $pcid = $l->create(NULL, 'TV10 1AF', 'Postcode', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');

        if (file_exists('/tmp/ut_paf0000000000.csv')) {
            unlink('/tmp/ut_paf0000000000.csv');
        }

        $p = new PAF($this->dbhr, $mock);
        $p->load(UT_DIR . '/php/misc/pc.csv', '/tmp/ut_paf');

        $csv = file_get_contents('/tmp/ut_paf0000000000.csv');
        error_log("CSV is $csv");

        # We've just loaded - should be the same.
        self::assertEquals(0, $p->update(UT_DIR . '/php/misc/pc.csv'));

        # Load a version where fields have changed and there's a new one.
        $t = file_get_contents(UT_DIR . '/php/misc/pc2.csv');
        $max = $this->dbhr->preQuery("SELECT MAX(udprn) AS max FROM paf_addresses;");
        $udprn = intval($max[0]['max']) + 1;
        $t = str_replace('zzz', $udprn, $t);
        file_put_contents('/tmp/ut.csv', $t);
        self::assertEquals(5, $p->update('/tmp/ut.csv'));
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

