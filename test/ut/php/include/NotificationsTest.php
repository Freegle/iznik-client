<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/Notifications.php';
require_once IZNIK_BASE . '/include/newsfeed/Newsfeed.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class notificationsTest extends IznikTestCase {
    private $dbhr, $dbhm;
    private $msgsSent = [];

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test Original Poster';", []);
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test Commenter';", []);

        # We test around Tuvalu.  If you're setting up Tuvalu Freegle you may need to change that.
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 8.3 AND swlat <= 8.7;");
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 179.1 AND swlat <= 179.3;");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'TV13%';");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE '??%';");
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

        $grids = $dbhr->preQuery("SELECT * FROM locations_grids WHERE swlng >= 179.1 AND swlng <= 179.3;");
        foreach ($grids as $grid) {
            $sql = "SELECT id FROM locations_grids WHERE MBRTouches (GeomFromText('POLYGON(({$grid['swlng']} {$grid['swlat']}, {$grid['swlng']} {$grid['nelat']}, {$grid['nelng']} {$grid['nelat']}, {$grid['nelng']} {$grid['swlat']}, {$grid['swlng']} {$grid['swlat']}))'), box);";
            $touches = $dbhr->preQuery($sql);
            foreach ($touches as $touch) {
                $sql = "INSERT IGNORE INTO locations_grids_touches (gridid, touches) VALUES (?, ?);";
                $rc = $dbhm->preExec($sql, [ $grid['id'], $touch['id'] ]);
            }
        }
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function sendMock($mailer, $message) {
        $this->msgsSent[] = $message->toString();
    }

    public function testEmail() {
        error_log(__METHOD__);

        $l = new Location($this->dbhr, $this->dbhm);
        $lid = $l->create(NULL, 'Tuvalu High Street', 'Road', 'POINT(179.2167 8.53333)');
        assertNotNull($lid);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid1 = $u->create(NULL, NULL, 'Test Original Poster');
        error_log("Created user $uid1");
        $u = User::get($this->dbhr, $this->dbhm, $uid1);
        $u->setPrivate('lastlocation', $lid);
        $email = 'test1@test.com';
        error_log("Added email " . $u->addEmail($email) . " vs " . $u->getEmailPreferred());

        $uid2 = $u->create(NULL, NULL, 'Test Commenter');
        error_log("Created user $uid2");
        $u = User::get($this->dbhr, $this->dbhm, $uid2);
        $u->setPrivate('lastlocation', $lid);
        $email = 'test2@test.com';
        error_log("Added email " . $u->addEmail($email));

        $n = $this->getMockBuilder('Notifications')
            ->setConstructorArgs(array($this->dbhm, $this->dbhm))
            ->setMethods(array('sendIt'))
            ->getMock();

        $n->method('sendIt')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        $f = new Newsfeed($this->dbhm, $this->dbhm);
        $nid = $f->create(Newsfeed::TYPE_MESSAGE, $uid1, 'Test message');
        error_log("Original post $nid");
        $rid = $f->create(Newsfeed::TYPE_MESSAGE, $uid2, 'Test reply', NULL, NULL, $nid);
        error_log("Reply $rid");
        $n->add($uid2, $uid1, Notifications::TYPE_LOVED_POST, $nid);

        self::assertEquals(2, $n->sendEmails($uid1, '0 seconds ago', '7 days ago'));
        $rid2 = $f->create(Newsfeed::TYPE_MESSAGE, $uid1, 'Test reply to reply', NULL, NULL, $nid);
        error_log("Reply $rid2");

        $n->add($uid1, $uid2, Notifications::TYPE_LOVED_COMMENT, $rid);
//        $n->add($uid2, $uid1, Notifications::TYPE_COMMENT_ON_YOUR_POST, $nid);
//        $n->add($uid1, $uid2, Notifications::TYPE_COMMENT_ON_COMMENT, $rid);

        self::assertEquals(2, $n->sendEmails($uid2, '0 seconds ago', '7 days ago'));

        error_log(__METHOD__ . " end");
    }
}

