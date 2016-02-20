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

    public function getPath() {
        # We serve up our attachment names as though they are files.
        # When these are fetched it will go through image.php
        return("/img_{$this->id}.jpg");
    }

    public function getPublic() {
        $ret = array(
            'id' => $this->id
        );

        # We get some pictures as images, as we'd expect, and some as octet-stream
        if (stripos($this->contentType, 'image') !== FALSE || stripos($this->contentType, 'octet-stream') !== FALSE) {
            # It's an image.  That's the only type we support.
            $ret['path'] = $this->getPath();
        }

        return($ret);
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
        $sql = "SELECT id FROM messages_attachments WHERE msgid = ? ORDER BY id;";
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