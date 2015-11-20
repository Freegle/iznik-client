<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';

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

        $this->dbhm->exec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
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

    public function testErrors() {
        error_log(__METHOD__);

        # Create duplicate group
        $g = new Group($this->dbhr, $this->dbhm);
        $id = $g->create('testgroup', Group::GROUP_REUSE);
        assertNotNull($id);
        $id2 = $g->create('testgroup', Group::GROUP_REUSE);
        assertNull($id2);

        $id2 = $g->findByShortName('zzzz');
        assertNull($id2);

        # Test errors in set members
        error_log("Set Members errors");
        $u = new User($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');
        $this->user = new User($this->dbhr, $this->dbhm, $this->uid);
        $this->user->addEmail('test@test.com');
        $this->user->addMembership($id);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $g = new Group($this->dbhr, $this->dbhm, $id);
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $g->setDbhm($mock);
        $rc = $g->setMembers([]);
        assertFalse($rc);

        $members = $g->getMembers();
        assertEquals(1, count($members));
        assertEquals('test@test.com', $members[0]['emails'][0]['email']);

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('exec'))
            ->getMock();
        $mock->method('exec')->willThrowException(new Exception());
        $g->setDbhm($mock);

        $rc = $g->setMembers([]);
        assertFalse($rc);

        $members = $g->getMembers();
        assertEquals(1, count($members));
        assertEquals('test@test.com', $members[0]['emails'][0]['email']);

        error_log(__METHOD__ . " end");
    }
}

