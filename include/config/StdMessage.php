<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class StdMessage extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'configid', 'title', 'action', 'subjpref', 'subjsuff', 'body', 'rarelyused',
        'autosend', 'newmodstatus', 'newdelstatus', 'edittext', 'insert');

    var $settableatts = array('configid', 'title', 'action', 'subjpref', 'subjsuff', 'body', 'rarelyused',
        'autosend', 'newmodstatus', 'newdelstatus', 'edittext', 'insert');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'mod_stdmsgs', 'stdmsg', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($title, $cid) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO mod_stdmsgs (title, configid) VALUES (?,?)", [$title,$cid]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'mod_stdmsgs', 'stdmsg', $this->publicatts);
            $me = whoAmI($this->dbhr, $this->dbhm);
            $createdby = $me ? $me->getId() : NULL;
            $this->log->log([
                'type' => Log::TYPE_CONFIG,
                'subtype' => Log::SUBTYPE_CREATED,
                'byuser' => $createdby,
                'configid' => $cid,
                'stdmsgid' => $id,
                'text' => "StdMsg: $title"
            ]);

            return($id);
        } else {
            return(NULL);
        }
    }

    public function setAttributes($settings) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        foreach ($this->settableatts as $att) {
            if (array_key_exists($att, $settings)) {
                $this->setPrivate($att, $settings[$att]);
            }
        }

        $this->log->log([
            'type' => Log::TYPE_STDMSG,
            'subtype' => Log::SUBTYPE_EDIT,
            'stdmsgid' => $this->id,
            'configid' => $this->stdmsg['configid'],
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $this->getEditLog($settings)
        ]);
    }

    public function getPublic($stdmsgbody = TRUE) {
        $ret = $this->getAtts($this->publicatts);

        if (!$stdmsgbody) {
            # We want to save space.
            unset($ret['body']);
        }
        return($ret);
    }

    public function canModify() {
        $c = new ModConfig($this->dbhr, $this->dbhm, $this->stdmsg['configid']);
        return($c->canModify());
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM mod_stdmsgs WHERE id = ?;", [$this->id]);
        if ($rc) {
            $me = whoAmI($this->dbhr, $this->dbhm);
            $this->log->log([
                'type' => Log::TYPE_STDMSG,
                'subtype' => Log::SUBTYPE_DELETED,
                'byuser' => $me ? $me->getId() : NULL,
                'configid' => $this->stdmsg['configid'],
                'stdmsgid' => $this->id,
                'text' => "StdMsg; " . $this->stdmsg['title'],
            ]);
        }

        return($rc);
    }
}