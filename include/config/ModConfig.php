<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class ModConfig extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'createdby', 'fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
        'ccfollowupaddr', 'ccrejmembto', 'ccrejmembaddr', 'ccfollmembto', 'ccfollmembaddr', 'protected',
        'messageorder', 'network', 'coloursubj', 'subjreg', 'subjlen');

    var $settableatts = array('name', 'fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
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

    public function getPublic() {
        $ret = parent::getPublic();

        $ret['stdmsgs'] = [];

        $sql = "SELECT id FROM mod_stdmsgs WHERE configid = {$this->id};";
        $stdmsgs = $this->dbhr->query($sql);

        foreach ($stdmsgs as $stdmsg) {
            $s = new StdMessage($this->dbhr, $this->dbhm, $stdmsg['id']);
            $ret['stdmsgs'][] = $s->getPublic();
        }

        return($ret);
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

        if ($configid == NULL) {
            # This user has no config.  If there is another mod with one, then we use that.  This handles the case
            # of a new floundering mod who doesn't quite understand what's going on.  Well, partially.
            $sql = "SELECT configid FROM memberships WHERE groupid = ? AND role IN ('Moderator', 'Owner') AND configid IS NOT NULL;";
            $others = $this->dbhr->preQuery($sql, [ $groupid ]);
            foreach ($others as $other) {
                $configid = $other['configid'];
            }
        }

        return $configid;
    }

    public function setAttributes($settings) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        error_log("setAttrs " . var_export($settings, true));
        foreach ($this->settableatts as $att) {
            $val = pres($att, $settings);
            if ($val) {
                error_log("Set $att = " . json_encode($val));
                $this->setPrivate($att, $val);
            }
        }

        $this->log->log([
            'type' => Log::TYPE_CONFIG,
            'subtype' => Log::SUBTYPE_EDIT,
            'configid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $this->getEditLog($settings)
        ]);
    }

    public function delete() {
        $name = $this->modconfig['name'];
        $rc = $this->dbhm->preExec("DELETE FROM mod_configs WHERE id = ?;", [$this->id]);
        if ($rc) {
            $me = whoAmI($this->dbhr, $this->dbhm);
            $this->log->log([
                'type' => Log::TYPE_CONFIG,
                'subtype' => Log::SUBTYPE_DELETED,
                'byuser' => $me ? $me->getId() : NULL,
                'configid' => $this->id,
                'text' => $name
            ]);
        }

        return($rc);
    }
}