<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once(UT_DIR . '/../../include/config.php');
require_once IZNIK_BASE . '/include/session/Google.php';
require_once IZNIK_BASE . '/include/user/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class GoogleTest extends IznikTestCase {
    private $dbhr, $dbhm;
    public $people;

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
    
    public function client() {
        return($this);
    }

    public function get() {
        $me = new Google_Service_Plus_Person();
        $me->setId($this->googleId);
        $name = new Google_Service_Plus_PersonName();
        $name->setFormatted($this->googleName);
        $name->setFamilyName($this->googleLastName);
        $name->setGivenName($this->googleFirstName);
        $me->setName($name);
        $email = new Google_Service_Plus_PersonEmails();
        $email->setType('account');
        $email->setValue($this->googleEmail);
        $me->setEmails([$email]);
        return($me);
    }
    
    public function authenticate() {
    }

    public function getAccessToken() {
        return($this->accessToken);
    }
    
    public function testBasic() {
        error_log(__METHOD__);

        $g = new Google($this->dbhr, $this->dbhm, TRUE);
        list($session, $ret) = $g->login(1);
        assertEquals(2, $ret['ret']);

        # Basic successful login
        $mock = $this->getMockBuilder('Google')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, FALSE])
            ->setMethods(array('getClient', 'getPlus'))
            ->getMock();

        $mock->method('getClient')->willReturn($this);
        $mock->method('getPlus')->willReturn($this);
        $this->people = $this;

        $this->accessToken = '1234';
        $this->googleId = 1;
        $this->googleFirstName = 'Test';
        $this->googleLastName = 'User';
        $this->googleName = 'Test User';
        $this->googleEmail = 'test@test.com';

        list($session, $ret) = $mock->login(1);
        error_log("Returned " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $logins = $me->getLogins();
        error_log("Logins " . var_export($logins, TRUE));
        assertEquals(1, $logins[0]['uid']);

        # Log in again with a different email, triggering a merge.
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, "Test User2");
        $u->addEmail('test2@test.com');

        $this->googleEmail = 'test2@test.com';
        list($session, $ret) = $mock->login(1);
        assertEquals(0, $ret['ret']);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $emails = $me->getEmails();
        error_log("Emails " . var_export($emails, TRUE));
        assertEquals(2, count($emails));

        # Now delete an email, and log in again - should trigger an add of the email
        $me->removeEmail('test2@test.com');
        list($session, $ret) = $mock->login(1);
        assertEquals(0, $ret['ret']);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $emails = $me->getEmails();
        error_log("Emails " . var_export($emails, TRUE));
        assertEquals(2, count($emails));

        # Now delete the google login, and log in again - should trigger an add of the google id.
        assertEquals(1, $me->removeLogin('Google', 1));
        list($session, $ret) = $mock->login(1);
        assertEquals(0, $ret['ret']);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $emails = $me->getEmails();
        error_log("Emails " . var_export($emails, TRUE));
        assertEquals(2, count($emails));
        $logins = $me->getLogins();
        error_log("Logins " . var_export($logins, TRUE));
        assertEquals(1, count($logins));
        assertEquals(1, $logins[0]['uid']);

        error_log(__METHOD__ . " end");
    }
}

