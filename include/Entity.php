<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/Log.php');

# Base class used for groups, users, messages, with some basic fetching and attribute manipulation.
class Entity
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;
    var $id;
    var $publicatts = array();
    private $name;

    function fetch(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL, $table, $name, $publicatts)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->name = $name;
        $this->id = $id;
        $this->publicatts = $publicatts;

        if ($id) {
            $entities = $dbhr->preQuery("SELECT * FROM $table WHERE id = ?;", [$id]);
            foreach ($entities as $entity) {
                $this->$name = $entity;
            }
        }
    }

    private function getAtts($list) {
        $ret = array();
        foreach ($list as $att) {
            if (pres($att, $this->{$this->name})) {
                $ret[$att] = $this->{$this->name}[$att];
            } else {
                $ret[$att] = NULL;
            }
        }

        return($ret);
    }

    public function getPublic() {
        return($this->getAtts($this->publicatts));
    }

    public function getPrivate($att) {
        if (pres($att, $this->{$this->name})) {
            return($this->{$this->name}[$att]);
        } else {
            return(NULL);
        }
    }
}