<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

class Location extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'osm_id', 'name', 'type', 'geometry', 'popularity', 'gridid');

    /** @var  $log Log */
    private $log;
    var $loc;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'locations', 'loc', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($osm_id, $name, $type, $geometry, $gridid)
    {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO locations (osm_id, name, type, geometry, gridid) VALUES (?, ?, GeomFromText(?), ?)",
                [$osm_id, $name, $type, $geometry, $gridid]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'locations', 'loc', $this->publicatts);
            $this->log->log([
                'type' => Log::TYPE_LOCATION,
                'subtype' => Log::SUBTYPE_CREATED,
                'user' => $id,
                'text' => $name
            ]);

            return ($id);
        } else {
            return (NULL);
        }
    }

    public static function setGrids($dbhr, $dbhm) {
        # Set grid IDs for all locations which need it.
        $count = 0;
        $locs = $dbhr->preQuery("SELECT * FROM locations WHERE gridid IS NULL;");
        foreach ($locs as $loc) {
            $sql = "SELECT locations_grids.id AS gridid FROM `locations` INNER JOIN locations_grids ON locations.id = ? AND MBRIntersects(locations.geometry, locations_grids.box);";
            $grids = $dbhr->preQuery($sql, [ $loc['id'] ]);
            foreach ($grids as $grid) {
                $sql = "UPDATE locations SET gridid = ? WHERE id = ?;";
                $dbhm->preExec($sql, [ $grid['gridid'], $loc['id'] ]);
            }

            $count++;
            if ($count % 1000 == 0) {
                error_log("...$count");
            }
        }
    }

    public function delete()
    {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->preExec("DELETE FROM locations WHERE id = ?;", [$this->id]);
        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_LOCATION,
                'subtype' => Log::SUBTYPE_DELETED,
                'user' => $this->id,
                'byuser' => $me->getId(),
                'text' => $this->getName()
            ]);
        }

        return ($rc);
    }
}