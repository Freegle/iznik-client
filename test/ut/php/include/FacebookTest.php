<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/session/Facebook.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class FacebookTest extends IznikTestCase {
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

    public function getJavaScriptHelper() {
        return($this);
    }

    public function getAccessToken() {
        return($this);
    }

    public function getOAuth2Client() {
        return($this);
    }

    public function debugToken() {
        return($this);
    }

    public function validateAppId() {
        return(TRUE);
    }

    public function validateExpiration() {
        return(TRUE);
    }

    public function isLongLived() {
        return(FALSE);
    }

    public function getLongLivedAccessToken() {
        return($this->accessToken);
    }

    public function get() {
        return($this);
    }

    public function getGraphUser() {
        return($this);
    }

    public function asArray() {
        return([
            'id' => $this->facebookId,
            'first_name' => $this->facebookFirstName,
            'last_name' => $this->facebookLastName,
            'name' => $this->facebookName,
            'email' => $this->facebookEmail
        ]);
    }

    public function testBasic() {
        error_log(__METHOD__);

        $mock = $this->getMockBuilder('Facebook')
            ->setConstructorArgs([$this->dbhr, $this->dbhm])
            ->setMethods(array('getFB'))
            ->getMock();

        $mock->method('getFB')->willReturn($this);

        $this->accessToken = '1234';
        $this->facebookId = 1;
        $this->facebookFirstName = 'Test';
        $this->facebookLastName = 'User';
        $this->facebookName = 'Test User';
        $this->facebookEmail = 'test@test.com';

        list($session, $ret) = $mock->login();
        assertEquals(0, $ret['ret']);
        
        error_log(__METHOD__ . " end");
    }
}

