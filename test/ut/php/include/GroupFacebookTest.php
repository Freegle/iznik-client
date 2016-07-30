<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Facebook.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class groupFacebookTest extends IznikTestCase {
    private $dbhr, $dbhm;

    private $msgsSent = [];

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->tidy();
    }

    public function testBasic() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $count = $t->shareFrom(TRUE, "last week");
        assertGreaterThan(0, $count);

        $atts = $t->getPublic();
        assertEquals($atts['groupid'], $t->findById($atts['id']));

        $gid = $g->create('testgroup', Group::GROUP_UT);
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $t->set('test');
        assertEquals('test', $t->getPublic()['token']);

        error_log(__METHOD__ . " end");
    }

    public function testErrors() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => SQLHOST,
            'port' => SQLPORT,
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception('test', 100));

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $t->setDbhm($mock);
        try {
            # Will throw exception and then again in handler so we need to catch here.
            $t->shareFrom(true);
            assertTrue(FALSE);
        } catch (Exception $e) {}

        error_log(__METHOD__ . " end");
    }
}

