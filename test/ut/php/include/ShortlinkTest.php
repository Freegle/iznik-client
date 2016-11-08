<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/misc/Shortlink.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class shortlinkTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM shortlinks WHERE name LIKE 'test%';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testGroup() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_FREEGLE);
        $g->setPrivate('onhere', 1);

        $s = new Shortlink($this->dbhr, $this->dbhm);
        list($id, $url) = $s->resolve('testgroup');
        self::assertEquals('https://' . USER_SITE . '/explore/testgroup', $url);
        $g->setPrivate('onhere', 0);
        list($id, $url) = $s->resolve('testgroup');
        self::assertEquals('https://groups.yahoo.com/testgroup', $url);

        $s = new Shortlink($this->dbhr, $this->dbhm, $id);
        $atts = $s->getPublic();
        self::assertEquals('testgroup', $atts['name']);
        $s->delete();

        error_log(__METHOD__ . " end");
    }

    public function testOther() {
        error_log(__METHOD__);

        $s = new Shortlink($this->dbhr, $this->dbhm);
        $sid = $s->create('testurl', Shortlink::TYPE_OTHER, NULL, 'https://test.com');
        $atts = $s->getPublic();
        self::assertEquals('testurl', $atts['name']);
        self::assertEquals('https://test.com', $atts['url']);
        self::assertEquals('https://test.com', $s->resolve('testurl')[1]);
        $s->delete();

        error_log(__METHOD__ . " end");
    }
}


