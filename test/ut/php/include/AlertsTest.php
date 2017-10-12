<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/group/Alerts.php';
require_once IZNIK_BASE . '/include/user/User.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AlertTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->preExec("DELETE FROM groups WHERE `type` = 'UnitTest';");
    }

    protected function tearDown() {
        $this->dbhm->preExec("DELETE FROM alerts WHERE subject LIKE 'UT - please ignore';");
    }

    public function __construct() {
    }

    public function testMultiple() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_UT);

        $g->setPrivate('contactmail', 'test@test.com');
        $g->setPrivate('onyahoo', 0);

        $a = new Alert($this->dbhr, $this->dbhm);
        $id = $a->create(NULL, 'geeks', Alert::MODS, 'UT - please ignore', 'UT', 'UT', FALSE, FALSE);

        # Send - one external.
        self::assertEquals(1, $a->process($id, Group::GROUP_UT));

        # Again - should complete as no more UT groups.
        self::assertEquals(0, $a->process($id, Group::GROUP_UT));

        $a = new Alert($this->dbhr, $this->dbhm, $id);
        assertNotNull($a->getPrivate('complete'));

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_UT);

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($gid, User::ROLE_MODERATOR);

        $a = new Alert($this->dbhr, $this->dbhm);
        $id = $a->create(NULL, 'UT', Alert::MODS, 'UT - please ignore', 'UT', 'UT', FALSE, FALSE);

        global $dbconfig;

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $a->setDbhm($mock);

        self::assertEquals(0, $a->mailMods($id, $gid));

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('lastInsertId'))
            ->getMock();
        $mock->method('lastInsertId')->willThrowException(new Exception());
        $a->setDbhm($mock);

        $g->setPrivate('onyahoo', 1);
        self::assertEquals(0, $a->mailMods($id, $gid, FALSE));

        error_log(__METHOD__ . " end");
    }
}

