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
        'CTY' => 'County',
        'CED' => 'County Electoral Division',
        'DIS' => 'District',
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

        $rc = $this->dbhm->preExec("INSERT INTO authorities (name, area_code, polygon, simplified) VALUES (?,?,GeomFromText(?), ST_Simplify(GeomFromText(?), 0.001)) ON DUPLICATE KEY UPDATE polygon = GeomFromText(?), simplified = ST_Simplify(GeomFromText(?), 0.001));", [
            $name,
            $area_code,
            $polygon,
            $polygon,
            $polygon,
            $polygon
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhm, $this->dbhm, $id, 'authorities', 'auth', $this->publicatts);
            }
        }

        return($id);
    }

    public function search($term, $limit = 10) {
        # Remove any weird characters.
        $term = preg_replace("/[^[:alnum:][:space:]]/u", '', $term);

        $auths = $this->dbhr->preQuery("SELECT id, name FROM authorities WHERE name LIKE ? LIMIT $limit;", [
            $this->dbhr->quote("%$term%")
        ]);

        return($auths);
    }

    public function getPublic()
    {
        $auths = $this->dbhr->preQuery("SELECT id, name, AsText(simplified) AS polygon FROM authorities WHERE id = ?;", [
            $this->id
        ]);

        $atts = $auths[0];

        # Map the area code to something friendly.
        $atts['area_code'] = pres($atts['area_code'], $this->area_codes) ? $this->area_codes[$atts['area_code']] : NULL;
    }
}