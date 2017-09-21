<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/mail/Relevant.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/user/Search.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RelevantTest extends IznikTestCase
{
    private $dbhr, $dbhm;

    private $msgsSent = [];

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->msgsSent = [];

        # We test around Tuvalu.  If you're setting up Tuvalu Freegle you may need to change that.
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 8.3 AND swlat <= 8.7;");
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 179.1 AND swlat <= 179.3;");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE '??%';");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'TV13%';");
        for ($swlat = 8.3; $swlat <= 8.6; $swlat += 0.1) {
            for ($swlng = 179.1; $swlng <= 179.3; $swlng += 0.1) {
                $nelat = $swlat + 0.1;
                $nelng = $swlng + 0.1;

                # Use lng, lat order for geometry because the OSM data uses that.
                $dbhm->preExec("INSERT IGNORE INTO locations_grids (swlat, swlng, nelat, nelng, box) VALUES (?, ?, ?, ?, GeomFromText('POLYGON(($swlng $swlat, $nelng $swlat, $nelng $nelat, $swlng $nelat, $swlng $swlat))'));",
                    [
                        $swlat,
                        $swlng,
                        $nelat,
                        $nelng
                    ]);
            }
        }

        $this->tidy();
    }

    public function sendMock($mailer, $message)
    {
        $this->msgsSent[] = $message->toString();
    }

    public function testInterested()
    {
        error_log(__METHOD__);

        # Create two locations
        $l = new Location($this->dbhr, $this->dbhm);
        $areaid = $l->create(NULL, 'Tuvalu Central', 'Polygon', 'POLYGON((179.21 8.53, 179.21 8.54, 179.22 8.54, 179.22 8.53, 179.21 8.53, 179.21 8.53))', 0);
        $fullpcid = $l->create(NULL, 'TV13 1HH', 'Postcode', 'POINT(179.2167 8.53333)', 0);

        # Create a user
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        error_log("Created user $uid");
        $email = 'ut-' . rand() . '@test.com';
        $u->addEmail($email, 0, FALSE);
        $u->addEmail('ut-' . rand() . '@' . USER_DOMAIN, 0, FALSE);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));
        error_log("Emails before first " . var_export($u->getEmails(), TRUE));

        # Post a WANTED, an OFFER and a search.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_FREEGLE);
        $g->setPrivate('lat', 8.53333);
        $g->setPrivate('lng', 179.2167);
        $g->setPrivate('poly', 'POLYGON((179.21 8.53, 179.21 8.54, 179.22 8.54, 179.22 8.53, 179.21 8.53, 179.21 8.53))');
        $g->setPrivate('onhere', 1);
        $r = new MailRouter($this->dbhr, $this->dbhm);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'testgroup@yahoogroups.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        error_log("User $uid $email message $id");
        error_log("Emails after first " . var_export($u->getEmails(), TRUE));

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'WANTED: Another thing (location)', $msg);
        $msg = str_replace('test@test.com', $email, $msg);
        $id = $r->received(Message::YAHOO_APPROVED, $email, 'testgroup@yahoogroups.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        error_log("User $uid $email message $id");

        error_log("Emails after second " . var_export($u->getEmails(), TRUE));

        $l = new Location($this->dbhr, $this->dbhm);
        $lid = $l->findByName('TV13 1HH');
        $s = new UserSearch($this->dbhr, $this->dbhm);
        $sid = $s->create($uid, NULL, "objets d'art", $lid);
        error_log("Got search entry $sid for loc $lid");

        $rl = new Relevant($this->dbhr, $this->dbhm);
        $ints = $rl->interestedIn($uid, Group::GROUP_FREEGLE);
//        error_log("Found interested 1 " . var_export($ints, TRUE));
//        assertEquals(1, count($ints));
//        $ints = $rl->interestedIn($uid2, Group::GROUP_FREEGLE);
//        error_log("Found interested 2 " . var_export($ints, TRUE));
//        assertEquals(2, count($ints));

        # Now search - no relevant messages at the moment.
        $msgs = $rl->getMessages($uid, $ints);
        error_log("Should be none " . var_export($msgs, TRUE));
        assertEquals(0, count($msgs));

        # Add two relevant messages.
        error_log("Add two relevant");
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: thing (location)', $msg);
        $msg = str_ireplace('Date: Sat, 22 Aug 2015 10:45:58 +0000', 'Date: ' . gmdate(DATE_RFC822, time()), $msg);
        $msg = str_replace('420816297', '420816298', $msg);
        $msg = str_replace('edwhuk', 'edwhuk1', $msg);
        $id1 = $r->received(Message::YAHOO_APPROVED, 'from2@test.com', 'to2@test.com', $msg, $gid);
        $m = new Message($this->dbhr, $this->dbhm, $id1);
        error_log("Relevant $id1 from " . $m->getFromuser());
        $u = new User($this->dbhr, $this->dbhm, $m->getFromuser());
        error_log("From user emails " . var_export($u->getEmails(), TRUE));
        assertNotNull($id);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', "OFFER: objets d'art (location)", $msg);
        $msg = str_ireplace('Date: Sat, 22 Aug 2015 10:45:58 +0000', 'Date: ' . gmdate(DATE_RFC822, time()), $msg);
        $msg = str_replace('420816297', '42081629', $msg);
        $msg = str_replace('edwhuk', 'edwhuk2', $msg);
        $id2 = $r->received(Message::YAHOO_APPROVED, 'from2@test.com', 'to2@test.com', $msg, $gid);
        $m = new Message($this->dbhr, $this->dbhm, $id2);
        error_log("Relevant $id2 from " . $m->getFromuser());
        assertNotNull($id2);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        error_log("Relevant messages $id1 and $id2");

        # Now send messages - should find these.
        $u->setPrivate('lastrelevantcheck', NULL);
        $mock = $this->getMockBuilder('Relevant')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, NULL, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->setPrivate('lastrelevantcheck', NULL);
        self::assertEquals(1, $mock->sendMessages($uid));

        $msgs = $this->msgsSent;
        error_log("Should return $id1 and $id2 ");
        assertEquals(1, count($msgs));

        # Long line might split id - hack out QP encoding.
        $msgs = preg_replace("/\=\r\n/", "", $msgs[0]);
        error_log($msgs);
        self::assertNotFalse(strpos($msgs, $id1));
        self::assertNotFalse(strpos($msgs, $id2));

        # Record the check.  Sleep to ensure that the messages we already have are longer ago than when we
        # say the check happened, otherwise we might get them back again - which is ok in real messages
        # but not for UT where it needs to be predictable.
        sleep(2);
        $rl->recordCheck($uid);
        sleep(2);

        # Now shouldn't find any.
        $msgs = $rl->getMessages($uid, $ints);
        error_log("Should be none " . var_export($msgs, TRUE));
        assertEquals(0, count($msgs));

        # Exception
        $mock = $this->getMockBuilder('Relevant')
            ->setConstructorArgs([$this->dbhr, $this->dbhm, NULL, TRUE])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->willThrowException(new Exception());

        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->setPrivate('lastrelevantcheck', NULL);
        self::assertEquals(0, $mock->sendMessages($uid));

        error_log(__METHOD__ . " end");
    }
}

