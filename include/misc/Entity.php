<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

# Base class used for groups, users, messages, with some basic fetching and attribute manipulation.
class Entity
{
    /** @var  $dbhr LoggedPDO */
    var $dbhr;
    /** @var  $dbhm LoggedPDO */
    var $dbhm;
    var $id;
    var $publicatts = array();
    private $name, $table;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    function fetch(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL, $table, $name, $publicatts, $fetched = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->name = $name;
        $this->$name = NULL;
        $this->id = NULL;
        $this->publicatts = $publicatts;
        $this->table = $table;

        if ($id) {
            $entities = $fetched ? [ $fetched ] : $dbhr->preQuery("SELECT * FROM $table WHERE id = ?;", [$id]);
            foreach ($entities as $entity) {
                $this->$name = $entity;
                $this->id = $id;
            }
        }
    }

    public function getAtts($list) {
        $ret = array();
        foreach ($list as $att) {
            if ($this->{$this->name} && array_key_exists($att, $this->{$this->name})) {
                $ret[$att] = $this->{$this->name}[$att];
            } else {
                $ret[$att] = NULL;
            }
        }

        return($ret);
    }

    public function getPublic() {
        $ret = $this->getAtts($this->publicatts);
        return($ret);
    }

    public function getPrivate($att) {
        if (pres($att, $this->{$this->name})) {
            return($this->{$this->name}[$att]);
        } else {
            return(NULL);
        }
    }

    public function getEditLog($new) {
        $old = $this->{$this->name};

        $edit = [];
        foreach ($new as $att => $val) {
            $oldval = json_encode(pres($att, $old) ? $old[$att] : NULL);
            if ($oldval != json_encode($val)) {
                $edit[] = [
                    $att => [
                        'old' => pres($att, $old) ? $old[$att] : NULL,
                        'new' => $val
                    ]
                ];
            }
        }

        $str = json_encode($edit);

        return($str);
    }

    public function setPrivate($att, $val) {
        if ($this->{$this->name}[$att] != $val) {
            $rc = $this->dbhm->preExec("UPDATE {$this->table} SET `$att` = ? WHERE id = {$this->id};", [$val]);
            if ($rc) {
                $this->{$this->name}[$att] = $val;
            }
        }
    }

    public function setAttributes($settings) {
        foreach ($this->settableatts as $att) {
            if (array_key_exists($att, $settings)) {
                $this->setPrivate($att, $settings[$att]);
            }
        }
    }
}