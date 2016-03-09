<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class Item extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'popularity', 'weight', 'updated');
    var $settableatts = array('name', 'popularity', 'weight');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'items', 'item', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($name) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO items (name) VALUES (?);", [ $name ]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'items', 'item', $this->publicatts);
            return($id);
        } else {
            return(NULL);
        }
    }

    public function setAttributes($settings) {
        foreach ($this->settableatts as $att) {
            if (array_key_exists($att, $settings)) {
                $this->setPrivate($att, $settings[$att]);
            }
        }
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM items WHERE id = ?;", [$this->id]);
        return($rc);
    }
}