<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/misc/Preview.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class PreviewTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->dbhm->preExec("DELETE FROM link_previews WHERE url = 'https://google.co.uk';");
        $this->dbhm->preExec("DELETE FROM link_previews WHERE url = 'https://google.ca';");
    }

    protected function tearDown() {
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $l = new Preview($this->dbhr, $this->dbhm);
        $id = $l->create('https://google.co.uk');
        assertNotNull($id);
        $atts = $l->getPublic();
        error_log("Atts " . var_export($atts, TRUE));
        self::assertEquals('Google', $atts['title']);

        $id2 = $l->get('https://google.co.uk');
        self::assertEquals($id, $id2);

        $id3 = $l->get('https://google.ca');
        assertNotNull($id3);

        error_log(__METHOD__ . " end");
    }

    public function testInvalid() {
        error_log(__METHOD__);

        $l = new Preview($this->dbhr, $this->dbhm);
        $id = $l->create('https://googfsdfasdfdsafsdafsdafsdafsd.com');
        assertNotNull($id);
        $atts = $l->getPublic();
        self::assertEquals(1, $atts['invalid']);

        $id = $l->create('https://googfsdfasdfdsafsdafsdafsdafsd');
        assertNotNull($id);
        $atts = $l->getPublic();
        self::assertEquals(1, $atts['invalid']);

        $id = $l->create('https://dbltest.com');
        assertNotNull($id);
        $atts = $l->getPublic();
        self::assertEquals(1, $atts['spam']);

        error_log(__METHOD__ . " end");
    }
}

