<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/message/Attachment.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AttachmentTest extends IznikTestCase {
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

    public function testIdentify() {
        error_log(__METHOD__);

        $data = file_get_contents('images/chair.jpg');
        $a = new Attachment($this->dbhr, $this->dbhm);
        $attid = $a->create(NULL, 'image/jpeg', $data);
        assertNotNull($attid);

        $a = new Attachment($this->dbhr, $this->dbhm, $attid);

        $idents = $a->identify();
        error_log(var_export($idents, TRUE));
        assertEquals('chair', $idents[0]['name']);

        error_log(__METHOD__ . " end");
    }
}

