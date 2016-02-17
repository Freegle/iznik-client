<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/config/StdMessage.php');

class ModConfig extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'createdby', 'fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
        'ccfollowupaddr', 'ccrejmembto', 'ccrejmembaddr', 'ccfollmembto', 'ccfollmembaddr', 'protected',
        'messageorder', 'network', 'coloursubj', 'subjreg', 'subjlen', 'default');

    var $settableatts = array('name', 'fromname', 'ccrejectto', 'ccrejectaddr', 'ccfollowupto',
        'ccfollowupaddr', 'ccrejmembto', 'ccrejmembaddr', 'ccfollmembto', 'ccfollmembaddr', 'protected',
        'messageorder', 'network', 'coloursubj', 'subjreg', 'subjlen');

    /** @var  $log Log */
    private $log;

    const CANSEE_CREATED = 'Created';
    const CANSEE_DEFAULT = 'Default';
    const CANSEE_SHARED = 'Shared';

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

    public function create($name, $createdby = NULL, $copyid = NULL) {
        try {
            if (!$createdby) {
                # Create as current user
                $me = whoAmI($this->dbhr, $this->dbhm);
                $createdby = $me ? $me->getId() : NULL;
            }

            if (!$copyid) {
                # Simple create of an empty config.
                $rc = $this->dbhm->preExec("INSERT INTO mod_configs (name, createdby) VALUES (?, ?)", [$name, $createdby]);
                $id = $this->dbhm->lastInsertId();
            } else {
                # We need to copy an existing config.  No need for a transaction as worst case we leave a bad config,
                # which a mod is likely to spot and not use.
                #
                # First copy the basic settings.
                $cfrom = new ModConfig($this->dbhr, $this->dbhm, $copyid);
                $rc = $this->dbhm->preExec("INSERT INTO mod_configs (ccrejectto, ccrejectaddr, ccfollowupto, ccfollowupaddr, ccrejmembto, ccrejmembaddr, ccfollmembto, ccfollmembaddr, network, coloursubj, subjlen) SELECT ccrejectto, ccrejectaddr, ccfollowupto, ccfollowupaddr, ccrejmembto, ccrejmembaddr, ccfollmembto, ccfollmembaddr, network, coloursubj, subjlen FROM mod_configs WHERE id = ?;", [ $copyid ]);
                $toid = $this->dbhm->lastInsertId();
                $cto = new ModConfig($this->dbhr, $this->dbhm, $toid);

                # Now set up the new name and the fact that we created it.
                $cto->setPrivate('name', $name);
                $cto->setPrivate('createdby', $createdby);

                # Now copy the existing standard messages.  Doing it this way will preserve any custom order.
                $stdmsgs = $cfrom->getPublic()['stdmsgs'];
                $order = [];
                foreach ($stdmsgs as $stdmsg) {
                    $sfrom = new StdMessage($this->dbhr, $this->dbhm, $stdmsg['id']);
                    $atts = $sfrom->getPublic();
                    $sid = $sfrom->create($atts['title'], $toid);
                    $sto = new StdMessage($this->dbhr, $this->dbhm, $sid);
                    unset($atts['id']);
                    unset($atts['title']);
                    unset($atts['configid']);

                    foreach ($atts as $att => $val) {
                        $sto->setPrivate($att, $val);
                    }

                    $order[] = $sid;
                }

                $cto->setPrivate('messageorder', json_encode($order, true));

                # Now copy the existing bulk ops
                $bulkops = $cfrom->getPublic()['bulkops'];
                foreach ($bulkops as $bulkop) {
                    $bfrom = new BulkOp($this->dbhr, $this->dbhm, $bulkop['id']);
                    $atts = $bfrom->getPublic();
                    $bid = $bfrom->create($atts['title'], $toid);
                    $bto = new BulkOp($this->dbhr, $this->dbhm, $bid);
                    unset($atts['id']);
                    unset($atts['title']);
                    unset($atts['configid']);

                    foreach ($atts as $att => $val) {
                        $bto->setPrivate($att, $val);
                    }
                }

                $id = $toid;
            }
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

    public function getPublic($stdmsgbody = TRUE) {
        $ret = parent::getPublic();

        # If the creating mod has been deleted, then we need to ensure that the config is no longer protected.
        $ret['protected'] = $ret['createdby'] == NULL ? 0 : $ret['protected'];

        $ret['stdmsgs'] = [];

        $sql = "SELECT id FROM mod_stdmsgs WHERE configid = {$this->id};";
        $stdmsgs = $this->dbhr->query($sql);

        foreach ($stdmsgs as $stdmsg) {
            $s = new StdMessage($this->dbhr, $this->dbhm, $stdmsg['id']);
            $ret['stdmsgs'][] = $s->getPublic($stdmsgbody);
        }

        $sql = "SELECT id FROM mod_bulkops WHERE configid = {$this->id};";
        $bulkops = $this->dbhr->query($sql);

        foreach ($bulkops as $bulkop) {
            $s = new BulkOp($this->dbhr, $this->dbhm, $bulkop['id']);
            $ret['bulkops'][] = $s->getPublic();
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

                # Record that for next time.
                $sql = "UPDATE memberships SET configid = ? WHERE groupid = ? AND userid = ?;";
                $this->dbhm->preExec($sql, [ $configid, $groupid, $modid ]);
            }
        }

        return $configid;
    }

    public function setAttributes($settings) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        foreach ($this->settableatts as $att) {
            if (array_key_exists($att, $settings)) {
                $this->setPrivate($att, $settings[$att]);
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

    public function canModify() {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;
        $systemrole = $me ? $me->getPublic()['systemrole'] : User::SYSTEMROLE_USER;

//        error_log("Canmod {$this->id} systemrole $systemrole");

        if ($systemrole == User::SYSTEMROLE_SUPPORT ||
            $systemrole == User::SYSTEMROLE_ADMIN) {
            # These can modify any config
            return (TRUE);
        }

//        error_log("Created {$this->modconfig['createdby']} vs $myid");
//        error_log("Protected {$this->modconfig['protected']}");
//        error_log("Cansee " . $this->canSee());

        return($this->canSee() && ($myid == $this->modconfig['createdby'] || !$this->modconfig['protected']));
    }

    public function canSee() {
        # Not quite see, exactly, as anyone can look at them.  But to be close enough to be able to run bulk ops.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        $systemrole = $me->getPublic()['systemrole'];

//        error_log("Cansee {$this->id} systemrole $systemrole");

        if ($systemrole == User::SYSTEMROLE_SUPPORT ||
            $systemrole == User::SYSTEMROLE_ADMIN) {
            # These can see any config.
            return(TRUE);
        }

        if ($systemrole == User::SYSTEMROLE_MODERATOR) {
            # Mods can see configs which
            # - we created
            # - are used by mods on groups on which we are a mod
            # - defaults
            $modships = $me ? $me->getModeratorships() : [];
            $modships = count($modships) == 0 ? [0] : $modships;

            $sql = "SELECT DISTINCT * FROM ((SELECT configid AS id FROM memberships WHERE groupid IN (" . implode(',', $modships) . ") AND configid IS NOT NULL) UNION (SELECT id FROM mod_configs WHERE createdby = $myid OR `default` = 1)) t WHERE id = {$this->id};";
            $ids = $this->dbhr->preQuery($sql);

            foreach ($ids as $id) {
                return (TRUE);
            }
        }

        return(FALSE);
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