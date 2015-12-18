<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/misc/Location.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class locationTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");

        # We test around Tuvalu.  If you're setting up Tuvalu Freegle you may need to change that.
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 8.3 AND swlat <= 8.7;");
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 179.1 AND swlat <= 179.3;");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvulu%';");
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
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $l = new Location($this->dbhr, $this->dbhm);
        $id = $l->create(NULL, 'Tuvulu High Street', 'Road', 'POINT(179.2167 8.53333)', NULL);
        $l = new Location($this->dbhr, $this->dbhm, $id);
        assertNull($l->getGrid());

        Location::setGrids($this->dbhr, $this->dbhm);

        $l = new Location($this->dbhr, $this->dbhm, $id);
        $atts = $l->getPublic();
        error_log("Created loc " . var_export($atts, true));
        $gridid = $atts['gridid'];
        $grid = $l->getGrid();
        error_log("Grid " . var_export($grid, true));
        assertEquals($gridid, $grid['id']);
        assertEquals(8.5, $grid['swlat']);
        assertEquals(179.2, $grid['swlng']);

        assertEquals(1, $l->delete());

        error_log(__METHOD__ . " end");
    }

    public function testError() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $l = new Location($this->dbhr, $this->dbhm);
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs([
                "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8",
                $dbconfig['user'], $dbconfig['pass'], array(), TRUE
            ])
            ->setMethods(array('preExec'))
            ->getMock();
        $mock->method('preExec')->willThrowException(new Exception());
        $l->setDbhm($mock);

        $id = $l->create(NULL, 'Tuvulu High Street', 'Road', 'POINT(179.2167 8.53333)', NULL);
        assertNull($id);

        error_log(__METHOD__ . " end");
    }

    public function testSearch() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);
        error_log("Created group $gid");
        $g = new Group($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.4);

        $l = new Location($this->dbhr, $this->dbhm);
        $id = $l->create(NULL, 'Tuvulu High Street', 'Road', 'POINT(179.2167 8.53333)', NULL);
        Location::setGrids($this->dbhr, $this->dbhm);

        $l = new Location($this->dbhr, $this->dbhm, $id);

        $res = $l->search("tuvulu", $gid);
        error_log(var_export($res, true));
        assertEquals(1, count($res));
        assertEquals($id, $res[0]['id']);

        $res = $l->search("high", $gid);
        assertEquals(1, count($res));
        assertEquals($id, $res[0]['id']);

        $res = $l->search("stre", $gid);
        assertEquals(1, count($res));
        assertEquals($id, $res[0]['id']);

        $res = $l->search("high street", $gid);
        assertEquals(1, count($res));
        assertEquals($id, $res[0]['id']);

        # Make sure that exact matches trump prefix matches
        $id2 = $l->create(NULL, 'Tuvulu High', 'Road', 'POINT(179.2167 8.53333)', NULL);
        Location::setGrids($this->dbhr, $this->dbhm);

        $res = $l->search("tuvulu high", $gid, 1);
        assertEquals(1, count($res));
        assertEquals($id2, $res[0]['id']);

        assertEquals(1, $l->delete());

        error_log(__METHOD__ . " end");
    }
}

