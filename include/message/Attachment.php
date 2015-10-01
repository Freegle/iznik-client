<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

# This is a base class
class Attachment
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $id, $contentType;

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->id = $id;

        if ($id) {
            $sql = "SELECT contenttype FROM messages_attachments WHERE id = ?;";
            $atts = $this->dbhr->preQuery($sql, [$id]);
            foreach ($atts as $att) {
                $this->contentType = $att['contenttype'];
            }
        }
    }

    public static function getById($dbhr, $dbhm, $id) {
        $sql = "SELECT id FROM messages_attachments;";
        $atts = $dbhr->preQuery($sql, [$id]);
        $ret = [];
        foreach ($atts as $att) {
            $ret[] = new Attachment($dbhr, $dbhm, $att['id']);
        }

        return($ret);
    }

    public function getData() {
        $ret = NULL;

        $sql = "SELECT data FROM messages_attachments WHERE id = ?;";
        $datas = $this->dbhr->preQuery($sql, [$this->id]);
        foreach ($datas as $data) {
            $ret = $data['data'];
        }

        return($ret);
    }
}