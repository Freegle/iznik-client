<?php

require_once(BASE_DIR . '/include/utils.php');

# Base class used for groups, users, messages, with some basic fetching and attribute manipulation.
class Entity
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;
    private $publicatts = array();
    private $name;
    private $id;

    function fetch(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL, $table, $name, $publicatts)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->name = $name;
        $this->id = $id;
        $this->publicatts = $publicatts;

        error_log("Id $id");

        if ($id) {
            $entities = $dbhr->preQuery("SELECT * FROM $table WHERE id = ?;", [$id]);
            foreach ($entities as $entity) {
                error_log("Set $name property");
                $this->$name = $entity;
            }
        }
    }

    private function getAtts($list) {
        $ret = array();
        error_log("Get atts " . var_export($list, true));
        foreach ($list as $att) {
            if (pres($att, $this->{$this->name})) {
                error_log("Found $att");
                $ret[$att] = $this->{$this->name}[$att];
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