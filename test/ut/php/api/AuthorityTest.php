<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/misc/Authority.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class authorityAPITest extends IznikAPITestCase
{
    public $dbhr, $dbhm;

    private $count = 0;

    protected function setUp()
    {
        parent::setUp();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM authorities WHERE name LIKE 'UTAuth%';");
    }

    protected function tearDown()
    {
        $this->dbhm->preExec("DELETE FROM authorities WHERE name LIKE 'UTAuth%';");
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testBasic()
    {
        error_log(__METHOD__);

        $a = new Authority($this->dbhr, $this->dbhm);

        $id = $a->create("UTAuth", 'GLA', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');

        $ret = $this->call('authority', 'GET', [
            'id' => $id
        ]);

        error_log("Get returned " . var_export($ret, TRUE));

        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['authority']['id']);
        assertEquals('UTAuth', $ret['authority']['name']);

        $ret = $this->call('authority', 'GET', [
            'search' => 'utau'
        ]);

        error_log("Search returned " . var_export($ret, TRUE));

        assertEquals(0, $ret['ret']);
        assertEquals(1, count($ret['authorities']));
        assertEquals($id, $ret['authorities'][0]['id']);

        error_log(__METHOD__ . " end");
    }
}
