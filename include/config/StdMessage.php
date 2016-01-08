<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class StdMessage extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'configid', 'title', 'action', 'subjpref', 'subjsuff', 'body', 'rarelyused',
        'autosend', 'newmodstatus', 'newdelstatus', 'edittext');

    var $settableatts = array('configid', 'title', 'action', 'subjpref', 'subjsuff', 'body', 'rarelyused',
        'autosend', 'newmodstatus', 'newdelstatus', 'edittext');

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
                'text' => $title
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
            'configid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $this->getEditLog($settings)
        ]);
    }


    public function canModify() {
        $c = new ModConfig($this->dbhr, $this->dbhm, $this->stdmsg['configid']);
        return($c->canModify());
    }

    private function evalIt($c, $to, $addr) {
        $ret = NULL;
        $to = $c->getPrivate($to);
        $addr = $c->getPrivate($addr);

        if ($to == 'Me') {
            $me = whoAmI($this->dbhr, $this->dbhm);
            $ret = $me->getEmailPreferred();
        } else if ($to = 'Specific') {
            $ret = $addr;
        }

        return($ret);
    }

    public function getBcc()
    {
        # Work out whether we have a BCC address to use for this stdmsg, by inspecting the relevant field
        # in the modconfig.
        $ret = NULL;

        if ($this->stdmsg['configid']) {
            $c = new ModConfig($this->dbhr, $this->dbhm, $this->stdmsg['configid']);

            switch ($this->stdmsg['action']) {
                case 'Approve':
                case 'Reject':
                case 'Leave':
                case 'Edward':
                    $ret = $this->evalIt($c, 'ccrejectto', 'ccrejectaddr');
                    break;
                case 'Approve Member':
                case 'Reject Member':
                case'Leave Member':
                    $ret = $this->evalIt($c, 'ccrejmembto', 'ccrejmembaddr');
                    break;
                case 'Leave Approved Message':
                case 'Delete Approved Message':
                    $ret = $this->evalIt($c, 'ccfollowupto', 'ccfollowupaddr');
                    break;
                case 'Leave Approved Member':
                case 'Delete Approved Member':
                    $ret = $this->evalIt($c, 'ccfollmembto', 'ccfollmembaddr');
                    break;
            }
        }

        return($ret);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM mod_stdmsgs WHERE id = ?;", [$this->id]);
        if ($rc) {
            $me = whoAmI($this->dbhr, $this->dbhm);
            $this->log->log([
                'type' => Log::TYPE_STDMSG,
                'subtype' => Log::SUBTYPE_DELETED,
                'byuser' => $me ? $me->getId() : NULL,
                'configid' => $this->id
            ]);
        }

        return($rc);
    }
}