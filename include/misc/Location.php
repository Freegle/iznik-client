<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/session/Session.php');

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
            $rc = $this->dbhm->preExec("INSERT INTO locations (osm_id, name, type, geometry, gridid) VALUES (?, ?, ?, GeomFromText(?), ?)",
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
        $locs = $dbhr->preQuery("SELECT * FROM locations WHERE gridid IS NULL;");
        foreach ($locs as $loc) {
            $sql = "SELECT locations_grids.id AS gridid FROM `locations` INNER JOIN locations_grids ON locations.id = ? AND MBRIntersects(locations.geometry, locations_grids.box) LIMIT 1;";
            $grids = $dbhr->preQuery($sql, [ $loc['id'] ]);
            foreach ($grids as $grid) {
                $sql = "UPDATE locations SET gridid = ? WHERE id = ?;";
                $dbhm->preExec($sql, [ $grid['gridid'], $loc['id'] ]);
            }
        }
    }

    public function getGrid() {
        $sql = "SELECT * FROM locations_grids WHERE id = ?;";
        $locs = $this->dbhr->preQuery($sql, [ $this->loc['gridid'] ]);
        foreach ($locs as $loc) {
            return($loc);
        }

        return(NULL);
    }

    public function search($term, $groupid, $limit = 10) {
        # We have a large table of locations.  We want to search within the ones which are close to this group, so
        # we look in the same or adjacent grid squares.
        $gridids = [];
        $ret = [];
        
        # Find the gridid for the group.
        $sql = "SELECT locations_grids.* FROM locations_grids INNER JOIN groups ON groups.id = ? AND swlat <= groups.lat AND swlng <= groups.lng AND nelat > groups.lat AND nelng > groups.lng;";
        $grids = $this->dbhr->preQuery($sql, [
            $groupid
        ]);
        
        foreach ($grids as $grid) {
            $gridids[] = $grid['id'];

            # Now find grids which touch that.  That avoids issues where our group is near the boundary of a grid square.
            $sql = "SELECT id FROM locations_grids WHERE MBRTouches (GeomFromText('POLYGON(({$grid['swlng']} {$grid['swlat']}, {$grid['swlng']} {$grid['nelat']}, {$grid['nelng']} {$grid['nelat']}, {$grid['nelng']} {$grid['swlat']}, {$grid['swlng']} {$grid['swlat']}))'), box);";
            $neighbours = $this->dbhr->query($sql);
            foreach ($neighbours as $neighbour) {
                $gridids[] = $neighbour['id'];
            }

            # Now we have a list of gridids within which we want to search.
            if (count($gridids) > 0) {
                # We do a simple %...% match.  We only use this search for autocomplete or identification of popular
                # locations, so we don't need a fuzzy search.  This will scan quite a lot of locations, because that
                # kind of search can't use the name index, but it is restricted by grids and therefore won't be
                # appalling.
                $term = $this->dbhr->quote($term);
                $term = preg_replace('/\'$/', '%\'', $term);
                $term = preg_replace('/^\'/', '\'%', $term);
                $sql = "SELECT * FROM locations WHERE name LIKE $term AND gridid IN (" . implode(',', $gridids) . ") ORDER BY popularity DESC LIMIT $limit;";
                $locs = $this->dbhr->query($sql);

                foreach ($locs as $loc) {
                    $ret[] = $loc;
                }
            }
        }

        return($ret);
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
                'byuser' => $me ? $me->getId() : NULL,
                'text' => $this->loc['name']
            ]);
        }

        return ($rc);
    }
}