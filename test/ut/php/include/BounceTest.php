<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/mail/Bounce.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class BounceTest extends IznikTestCase
{
    private $dbhr, $dbhm;

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->preExec("DELETE FROM bounces WHERE `to` = 'bounce-test@" . USER_DOMAIN . "';");
    }

    public function testBasic()
    {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $u->addEmail('test@test.com');

        $msg = file_get_contents('msgs/bounce');
        $b = new Bounce($this->dbhr, $this->dbhm);
        $id = $b->save("bounce-{$this->uid}-1234@" . USER_DOMAIN, $msg);
        assertNotNull($id);
        assertTrue($b->process($id));

        $this->waitBackground();
        $logs = $u->getPublic(NULL, FALSE, TRUE)['logs'];
        $log = $this->findLog(Log::TYPE_USER, Log::SUBTYPE_BOUNCE, $logs);
        assertEquals($this->uid, $log['user']['id']);

        $b->suspendMail($this->uid, 0, 0);
        $this->waitBackground();
        $logs = $u->getPublic(NULL, FALSE, TRUE)['logs'];
        $log = $this->findLog(Log::TYPE_USER, Log::SUBTYPE_SUSPEND_MAIL, $logs);
        assertEquals($this->uid, $log['user']['id']);

        error_log(__METHOD__ . " end");
    }
}
