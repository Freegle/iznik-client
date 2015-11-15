<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class ModConfig extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'createdby', 'fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
        'ccfollowupaddr', 'ccrejmembto', 'ccrejmembaddr', 'ccfollmembto', 'ccfollmembaddr', 'protected',
        'messageorder', 'network', 'coloursubj', 'subjreg', 'subjlen');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'mod_configs', 'modconfig', $this->publicatts);

        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($name, $createdby = NULL) {
        try {
            if (!$createdby) {
                # Create as current user
                $me = whoAmI($this->dbhr, $this->dbhm);
                $createdby = $me ? $me->getId() : NULL;
            }

            $rc = $this->dbhm->preExec("INSERT INTO mod_configs (name, createdby) VALUES (?, ?)", [$name, $createdby]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'mod_configs', 'modconfig', $this->publicatts);
            $this->log->log([
                'type' => Log::TYPE_CONFIG,
                'subtype' => Log::SUBTYPE_CREATED,
                'byuser' => $createdby,
                'configid' => $id,
                'text' => $name
            ]);

            return($id);
        } else {
            return(NULL);
        }
    }

    public function useOnGroup($modid, $groupid) {
        $sql = "UPDATE memberships SET configid = {$this->id} WHERE userid = ? AND groupid = ?;";
        $this->dbhm->preExec($sql, [
            $modid,
            $groupid
        ]);
    }

    public function getForGroup($modid, $groupid) {
        $sql = "SELECT configid FROM memberships WHERE userid = ? AND groupid = ?;";
        $confs = $this->dbhr->preQuery($sql, [
            $modid,
            $groupid
        ]);

        $configid = NULL;
        foreach ($confs as $conf) {
            $configid = $conf['configid'];
        }

        return $configid;
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM mod_configs WHERE id = ?;", [$this->id]);
        if ($rc) {
            $me = whoAmI($this->dbhr, $this->dbhm);
            $this->log->log([
                'type' => Log::TYPE_CONFIG,
                'subtype' => Log::SUBTYPE_DELETED,
                'byuser' => $me ? $me->getId() : NULL,
                'configid' => $this->id
            ]);
        }

        return($rc);
    }
}