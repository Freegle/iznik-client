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

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $t->getPostsToShare('a', "last week");
        $t->getPostsToShare($t->getPublic()['sharefrom'], "last week");

        $atts = $t->getPublic();
        assertEquals($atts['groupid'], $t->findById($atts['id']));

        $gid = $g->create('testgroup', Group::GROUP_UT);
        $t = new GroupFacebook($this->dbhr, $this->dbhm, $gid);
        $t->set($gid, 'test', 'test', 'test');
        assertEquals('test', $t->getPublic()['token']);

        error_log(__METHOD__ . " end");
    }
}

