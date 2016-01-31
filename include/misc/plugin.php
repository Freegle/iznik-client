<?php

require_once(IZNIK_BASE . '/include/utils.php');

class Plugin {
    /** @var  $dbhr LoggedPDO */
    private $dbhr;

    /** @var  $dbhm LoggedPDO */
    private $dbhm;

    function __construct($dbhr, $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->log = new Log($this->dbhr, $this->dbhm);
    }

    public function add($groupid, $data) {
        $sql = "INSERT INTO plugin (groupid, data) VALUES (?,?);";
        $this->dbhm->preExec($sql, [
            $groupid,
            json_encode($data)
        ]);

        $this->log->log([
            'type' => Log::TYPE_PLUGIN,
            'subtype' => Log::SUBTYPE_CREATED,
            'groupid' => $groupid,
            'text' => json_encode($data)
        ]);
    }

    public function get($groupid) {
        # Put a limit on to avoid swamping a particular user with work.  They'll pick it up again later.
        $sql = "SELECT * FROM plugin WHERE groupid = ? LIMIT 100;";
        $plugins = $this->dbhr->preQuery($sql, [ $groupid ]);

        foreach ($plugins as &$plugin) {
            $plugin['added'] = ISODate($plugin['added']);
        }

        return($plugins);
    }

    public function delete($id) {
        $sql = "SELECT * FROM plugin WHERE id = ?;";
        $plugins = $this->dbhr->preQuery($sql, [ $id ]);
        foreach ($plugins as $plugin) {
            $this->log->log([
                'type' => Log::TYPE_PLUGIN,
                'subtype' => Log::SUBTYPE_DELETED,
                'groupid' => $plugin['groupid'],
                'text' => json_encode($plugin)
            ]);
        }

        $sql = "DELETE FROM plugin WHERE id = ?;";
        return($this->dbhm->preExec($sql, [ $id ]));
    }
}