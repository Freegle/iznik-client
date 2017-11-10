<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');

class Authority extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'area_code', 'simplified');

    /** @var  $log Log */
    var $auth;

    # Friendly names for area codes as defined by OS.
    private $area_codes = [
        'CUN' => 'Country', // Not an OS code
        'CTY' => 'County Council',
        'CED' => 'County Electoral Division',
        'DIS' => 'District Council',
        'DIW' => 'District Ward',
        'EUR' => 'European Region',
        'GLA' => 'Greater London Authority',
        'LAC' => 'Greater London Authority Assembly Constituency',
        'LBO' => 'London Borough',
        'LBW' => 'London Borough Ward',
        'MTD' => 'Metropolitan District',
        'MTW' => 'Metropolitan District Ward',
        'SPE' => 'Scottish Parliament Electoral Region',
        'SPC' => 'Scottish Parliament Constituency',
        'UTA' => 'Unitary Authority',
        'UTE' => 'Unitary Authority Electoral Division',
        'UTW' => 'Unitary Authority Ward',
        'WAE' => 'Welsh Assembly Electoral Region',
        'WAC' => 'Welsh Assembly Constituency',
        'WMC' => 'Westminster Constituency'
    ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'authorities', 'auth', $this->publicatts);
    }

    public function create($name, $area_code, $polygon) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO authorities (name, area_code, polygon) VALUES (?,?,GeomFromText(?)) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), polygon = GeomFromText(?);", [
            $name,
            $area_code,
            $polygon,
            $polygon
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                try {
                    # The simplify call may fail.  We've seen this where there is a multipolygon, and the simplify
                    # returns a polygon with only two vertices, which then fails to update because it's invalid as
                    # a polygon.  So we do it separately and catch the exception.
                    $this->dbhm->preExec("UPDATE authorities SET simplified = ST_Simplify(GeomFromText(polygon), 0.001) WHERE id = ?;", [
                        $id
                    ]);
                } catch (Exception $e) {}

                $this->fetch($this->dbhm, $this->dbhm, $id, 'authorities', 'auth', $this->publicatts);
            }
        }

        return($id);
    }

    public function search($term, $limit = 10) {
        # Remove any weird characters.
        $term = preg_replace("/[^[:alnum:][:space:]]/u", '', $term);

        $auths = $this->dbhr->preQuery("SELECT id, name, area_code FROM authorities WHERE name LIKE " . $this->dbhr->quote("%$term%") . " LIMIT $limit;");

        foreach ($auths as &$auth) {
            $auth['area_code'] = pres($auth['area_code'], $this->area_codes) ? $this->area_codes[$auth['area_code']] : NULL;
        }

        return($auths);
    }

    public function getPublic()
    {
        $auths = $this->dbhr->preQuery("SELECT id, name, area_code, AsText(COALESCE(simplified, polygon)) AS polygon, Y(CENTROID(polygon)) AS lat, X(CENTROID(polygon)) AS lng FROM authorities WHERE id = ?;", [
            $this->id
        ]);

        $atts = $auths[0];

        # Map the area code to something friendly.
        $atts['area_code'] = pres($atts['area_code'], $this->area_codes) ? $this->area_codes[$atts['area_code']] : NULL;

        # Return the centre.
        $atts['centre'] = [
            'lat' => $atts['lat'],
            'lng' => $atts['lng']
        ];
        unset($atts['lat']);
        unset($atts['lng']);

        # Find groups which overlap with this area.
        $groups = $this->dbhr->preQuery("SELECT groups.id, nameshort, namefull, lat, lng, COALESCE(poly, polyofficial) AS poly, ST_Area(ST_Intersection(GeomFromText(COALESCE(poly, polyofficial)), COALESCE(simplified, polygon)))/ST_Area(GeomFromText(COALESCE(poly, polyofficial))) AS overlap FROM groups INNER JOIN authorities ON ST_Intersects(GeomFromText(COALESCE(poly, polyofficial)), polygon) WHERE type = ? AND publish = 1 AND onmap = 1 AND authorities.id = ?;", [
            Group::GROUP_FREEGLE,
            $atts['id']
        ]);

        foreach ($groups as &$group) {
            $group['namedisplay'] = pres('namefull', $group) ? $group['namefull'] : $group['nameshort'];
        }

        $atts['groups'] = $groups;

        return($atts);
    }
}