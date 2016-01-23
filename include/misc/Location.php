<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/session/Session.php');

class Location extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'osm_id', 'name', 'type', 'popularity', 'gridid', 'postcodeid', 'areaid');

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

    public function canon($str) {
        # There are some commom abbreviations which people might use, which we should expand.
        $str = preg_replace('/^St\b/', 'Saint', $str);
        $str = preg_replace('/\bSt\b/', 'Street', $str);
        $str = preg_replace('/\bRd\b/', 'Road', $str);
        $str = preg_replace('/\bAvenue\b/', 'Av', $str);
        $str = preg_replace('/\bDr\b/', 'Drive', $str);
        $str = preg_replace('/\bLn\b/', 'Lane', $str);
        $str = preg_replace('/\bPl\b/', 'Place', $str);
        $str = preg_replace('/\bSq\b/', 'Square', $str);
        $str = preg_replace('/\bCls\b/', 'Close', $str);
        $str = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $str));

        return($str);
    }

    public function create($osm_id, $name, $type, $geometry)
    {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO locations (osm_id, name, type, geometry, canon) VALUES (?, ?, ?, GeomFromText(?), ?)",
                [$osm_id, $name, $type, $geometry, $this->canon($name)]);
            $id = $this->dbhm->lastInsertId();
            
            if ($rc) {
                # Although this is something we can derive from the geometry, it speeds things up a lot to have it cached.
                $rc = $this->dbhm->preExec("UPDATE locations SET lng = X(GetCenterPoint(geometry)), lat = Y(GetCenterPoint(geometry)) WHERE id = ?;",
                    [ $id ]);
            }

            $sql = "SELECT locations_grids.id AS gridid FROM `locations` INNER JOIN locations_grids ON locations.id = ? AND MBRIntersects(locations.geometry, locations_grids.box) LIMIT 1;";
            $grids = $this->dbhr->preQuery($sql, [ $id ]);
            foreach ($grids as $grid) {
                $gridid = $grid['gridid'];
                $sql = "UPDATE locations SET gridid = ?, maxdimension = GetMaxDimension(geometry) WHERE id = ?;";
                $this->dbhm->preExec($sql, [ $grid['gridid'], $id ]);
            }

            $this->setParents($id, $gridid);
        } catch (Exception $e) {
            error_log("Location create exception");
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

    public function setParents($id, $gridid) {
        # For each location, we also want to store the area and first-part-postcode which this location is within.
        #
        # This allows us to standardise subjects on groups.
        $locs = $this->dbhr->preQuery("SELECT name, type, gridid, geometry, AsText(geometry) AS geomtext FROM locations WHERE id = ?;", [ $id ]);

        if (count($locs) > 0) {
            #echo "{$locs[0]['name']} ";

            # We can speed up our query if we restrict the search to this grid square and adjacent ones.
            $gridids = [];

            # Find the gridid for the group.
            $sql = "SELECT locations_grids.* FROM locations_grids WHERE id = ?;";
            $grids = $this->dbhr->preQuery($sql, [
                $locs[0]['gridid']
            ]);

            foreach ($grids as $grid) {
                $gridids[] = $grid['id'];

                # Now find grids which touch that.  That avoids issues where our group is near the boundary of a grid square.
                $sql = "SELECT id FROM locations_grids WHERE MBRTouches (GeomFromText('POLYGON(({$grid['swlng']} {$grid['swlat']}, {$grid['swlng']} {$grid['nelat']}, {$grid['nelng']} {$grid['nelat']}, {$grid['nelng']} {$grid['swlat']}, {$grid['swlng']} {$grid['swlat']}))'), box);";
                $neighbours = $this->dbhr->query($sql);
                foreach ($neighbours as $neighbour) {
                    $gridids[] = $neighbour['id'];
                }
            }

            if ($locs[0]['type'] == 'Postcode' && strlen($locs[0]['name']) <= 4) {
                # This location is itself what we want.
                #echo("  postcode {$locs[0]['name']}\n");
                $rc = $this->dbhm->preExec("UPDATE locations SET postcodeid = ? WHERE id = ?;", [ $id, $id ]);
            } else {
                $sql = "SELECT id, name FROM locations WHERE gridid IN (" . implode(',', $gridids) . ") AND type = 'Postcode' AND LENGTH(name) <= 4 ORDER BY ST_Distance(?, geometry) ASC LIMIT 1;";
                #echo ("Check postcode $sql " . implode(',', $gridids) . " {$locs[0]['geomtext']}\n");
                $intersects = $this->dbhr->preQuery($sql, [ $locs[0]['geometry']]);
                if (count($intersects) > 0) {
                    # TODO We might choose one which overlaps, but not as much as another one would.
                    #echo("  postcode {$intersects[0]['name']}\n");
                    $rc = $this->dbhm->preExec("UPDATE locations SET postcodeid = ? WHERE id = ?;", [ $intersects[0]['id'], $id ]);
                }
            }

            if ($locs[0]['type'] == 'Polygon') {
                # This location is itself what we want.
                #echo("  area {$locs[0]['name']}\n");
                $rc = $this->dbhm->preExec("UPDATE locations SET areaid = ? WHERE id = ?;", [ $id, $id ]);
            } else {
                # Search for an area which intersects this one.  We want the smallest such.
                $sql = "SELECT id, name, AsText(geometry) AS geomtext, GetMaxDimension(locations.geometry) AS span FROM locations WHERE gridid IN (" . implode(',', $gridids) . ") AND type = 'Polygon' AND MBRIntersects(geometry, ?) ORDER BY GetMaxDimension(locations.geometry) ASC LIMIT 10;";
                $intersects = $this->dbhr->preQuery($sql, [ $locs[0]['geometry']]);
                if (count($intersects) > 0) {
//                    foreach ($intersects as $intersect) {
//                        echo "...intesects {$intersect['id']} {$intersect['name']} {$intersect['span']}\n";
//                    }

                    # TODO We might choose one which overlaps, but not as much as another one would.
                    #echo("  area {$intersects[0]['name']}\n");
                    $rc = $this->dbhm->preExec("UPDATE locations SET areaid = ? WHERE id = ?;", [ $intersects[0]['id'], $id ]);
                }
            }
        }
    }

    public function getGrid() {
        $ret = NULL;
        $sql = "SELECT * FROM locations_grids WHERE id = ?;";
        $locs = $this->dbhr->preQuery($sql, [ $this->loc['gridid'] ]);
        foreach ($locs as $loc) {
            $ret = $loc;
        }

        return($ret);
    }

    public function search($term, $groupid, $limit = 10) {
        # We have a large table of locations.  We want to search within the ones which are close to this group, so
        # we look in the same or adjacent grid squares.
        $gridids = [];
        $ret = [];

        # We want to exclude some locations on a per group basis
        $exclgroup = " LEFT JOIN locations_excluded ON locations.id = locations_excluded.locationid AND locations_excluded.groupid = " . intval($groupid) . " ";

        # Exclude all numeric locations (there are some in OSM).  Also exclude amenities and shops, otherwise we get
        # some silly mappings (e.g. London).
        $exclude = " AND NOT canon REGEXP '^-?[0-9]+$' AND osm_amenity = 0 AND osm_shop = 0 AND locations_excluded.locationid IS NULL ";
        #$exclude = " AND NOT canon REGEXP '^-?[0-9]+$' AND osm_amenity = 0 AND osm_shop = 0 AND locations.id NOT IN (SELECT locationid FROM locations_excluded WHERE groupid = " . intval($groupid) . ") ";

        # Find the gridid for the group.
        $sql = "SELECT locations_grids.* FROM locations_grids INNER JOIN groups ON groups.id = ? AND swlat <= groups.lat AND swlng <= groups.lng AND nelat > groups.lat AND nelng > groups.lng;";
        #error_log("$sql $groupid");
        $grids = $this->dbhr->preQuery($sql, [
            $groupid
        ]);
        
        foreach ($grids as $grid) {
            $gridids[] = $grid['id'];

            # Now find grids within approximately 30 miles of that.
            #$sql = "SELECT id FROM locations_grids WHERE haversine(" . ($grid['swlat'] + 0.05) . ", " . ($grid['swlng'] + 0.05) . ", swlat + 0.05, swlng + 0.05) < 30;";
            $sql = "SELECT id FROM locations_grids WHERE ABS(" . $grid['swlat'] . " - swlat) <= 0.4 AND ABS(" . $grid['swlng']. " - swlng) <= 0.4;";
            $neighbours = $this->dbhr->query($sql);
            foreach ($neighbours as $neighbour) {
                $gridids[] = $neighbour['id'];
            }

            # Now we have a list of gridids within which we want to search.
            #error_log("Check grids " . implode(',', $gridids));
            if (count($gridids) > 0) {
                # First we do a simple match.  If the location is correct, that will find it quickly.
                $term2 = $this->dbhr->quote($this->canon($term));
                $sql = "SELECT locations.* FROM locations $exclgroup WHERE canon = $term2 AND gridid IN (" . implode(',', $gridids) . ") $exclude ORDER BY LENGTH(canon) ASC, popularity DESC LIMIT $limit;";
                #error_log("Simple match $sql");
                $locs = $this->dbhr->query($sql);

                foreach ($locs as $loc) {
                    $ret[] = $loc;
                    $limit--;
                }

                # Look for a known location which contains the location we've specified.  This will scan quite a lot of
                # locations, because that kind of search can't use the name index, but it is restricted by grids and therefore won't be
                # appalling.
                #
                # We want the matches that are closest in length to the term we're trying to match first
                # (you might have 'Stockbridge' and 'Stockbridge Church Of England Primary School'), then ordered
                # by most popular.
                if ($limit > 0) {
                    $sql = "SELECT locations.* FROM locations $exclgroup WHERE name REGEXP CONCAT('[[:<:]]', " . $this->dbhr->quote(trim($term)) . ", '[[:>:]]') AND gridid IN (" . implode(',', $gridids) . ") $exclude ORDER BY ABS(LENGTH(name) - " . strlen($term) . ") ASC, popularity DESC LIMIT $limit;";
                    #error_log("%..% $sql");
                    $locs = $this->dbhr->query($sql);

                    foreach ($locs as $loc) {
                        $ret[] = $loc;
                        $limit--;
                    }
                }

                if ($limit > 0) {
                    # We didn't find as many as we wanted.  It's possible that the location text actually contains
                    # two locations, most commonly a place and a postcode.  So do an (even slower) search to find
                    # locations in our table which appear somewhere in the subject.  Ignore very short ones.
                    #
                    # We also order to find the one most similar in length.
                    $sql = "SELECT locations.* FROM locations $exclgroup WHERE gridid IN (" . implode(',', $gridids) . ") AND LENGTH(canon) > 2 AND " . $this->dbhr->quote(trim($term)) . " REGEXP CONCAT('[[:<:]]', name, '[[:>:]]') $exclude ORDER BY ABS(LENGTH(name) - " . strlen($term) . "), GetMaxDimension(locations.geometry) ASC, popularity DESC LIMIT $limit;";
                    #error_log("Substring $sql");
                    $locs = $this->dbhr->query($sql);

                    foreach ($locs as $loc) {
                        $ret[] = $loc;
                        $limit--;
                    }
                }

                if ($limit > 0) {
                    # We still didn't find as many results as we wanted.  Do a (slow) search using a Damerau-Levenshtein
                    # distance function to spot typos, transpositions, spurious spaces etc.
                    $sql = "SELECT locations.* FROM locations $exclgroup WHERE gridid IN (" . implode(',', $gridids) . ") AND DAMLEVLIM(`canon`, " .
                        $this->dbhr->quote($this->canon($term)) . ", " . strlen($term) . ") < 2 $exclude ORDER BY ABS(LENGTH(canon) - " . strlen($term) . ") ASC, popularity DESC LIMIT $limit;";
                    #error_log("DamLeve $sql");
                    $locs = $this->dbhr->query($sql);

                    foreach ($locs as $loc) {
                        $ret[] = $loc;
                        $limit--;
                    }
                }

            }
        }

        # Don't return duplicates.
        $ret = array_unique($ret, SORT_REGULAR);

        # We might have acquired a few too many.
        $ret = array_slice($ret, 0, 10);

        return($ret);
    }

    public function locsForGroup($groupid) {
        # We have a large table of locations.  We want to return the ones which are close to this group, so
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
            #error_log("Get neighbours $sql");
            $neighbours = $this->dbhr->query($sql);
            foreach ($neighbours as $neighbour) {
                $gridids[] = $neighbour['id'];
            }

            # Now we have a list of gridids within which we want to find locations.
            if (count($gridids) > 0) {
                $sql = "SELECT locations.* FROM locations WHERE gridid IN (" . implode(',', $gridids) . ") ORDER BY popularity ASC;";
                $ret = $this->dbhr->query($sql);
            }
        }

        return($ret);
    }

    public function exclude($groupid, $userid) {
        # We want to exclude a specific location.  Exclude all locations with the same name as this one; our DB has
        # duplicate names.
        $sql = "SELECT id FROM locations WHERE name = (SELECT name FROM locations WHERE id = ?);";
        $locs = $this->dbhr->preQuery($sql, [ $this->id ]);

        foreach ($locs as $loc) {
            # Mark location as blocked for this group, so it won't be suggested again.
            $sql = "REPLACE INTO locations_excluded (locationid, groupid, userid) VALUES (?,?,?);";
            $rc = $this->dbhm->preExec($sql, [
                $loc['id'],
                $groupid,
                $userid
            ]);
        }

        # Not the end of the world if this doesn't work.
        return(TRUE);
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