<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/Entity.php');

class Group extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'nameshort', 'namefull', 'nameabbr', 'settings');

    const GROUP_REUSE = 'Reuse';
    const GROUP_FREEGLE = 'Freegle';

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'groups', 'group', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    public function create($shortname, $type) {
        $rc = $this->dbhm->preExec("INSERT INTO groups (nameshort, type) VALUES (?, ?)", [$shortname, $type]);
        $id = $this->dbhm->lastInsertId();

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'groups', 'group', $this->publicatts);
            $this->log->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_CREATED,
                'group' => $id,
                'text' => $shortname
            ]);
        }
        return($rc);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM groups WHERE id = ?;", [$this->id]);
        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_DELETED,
                'group' => $this->id
            ]);
        }

        return($rc);
    }
}