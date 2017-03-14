<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Address.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class abtestAPITest extends IznikAPITestCase
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

        $dbhm->preQuery("DELETE FROM abtest WHERE uid = 'UT';");
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testBasic()
    {
        error_log(__METHOD__);

        $ret = $this->call('abtest', 'POST', [
            'uid' => 'UT',
            'variant' => 'a',
            'shown' => TRUE
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('abtest', 'POST', [
            'uid' => 'UT',
            'variant' => 'b',
            'shown' => TRUE
        ]);
        assertEquals(0, $ret['ret']);

        $ret = $this->call('abtest', 'POST', [
            'uid' => 'UT',
            'variant' => 'a',
            'action' => TRUE
        ]);
        assertEquals(0, $ret['ret']);

        error_log(__METHOD__ . " end");
    }
}
