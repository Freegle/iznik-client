<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/lib/Address.php');

class PAF
{
    # Most fields are foreign key references to other tables.
    private $idfields1 = [
        'posttown',
        'dependentlocality',
        'doubledependentlocality',
        'thoroughfaredescriptor',
        'dependentthoroughfaredescriptor',
    ];
    
    private $idfields2 = [
        'buildingname',
        'subbuildingname',
        'pobox',
        'departmentname',
        'organisationname'
    ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    private function getRefId($table, $att, $val) {
        # Get an id from the appropriate table, inserting a new value if required.
        $id = NULL;

        if ($val && strlen(trim($val))) {
            $vals = $this->dbhr->preQuery("SELECT id FROM $table WHERE $att = ?;", [ $val ], FALSE, FALSE);

            if (count($vals) > 0) {
                $id = $vals[0]['id'];
            } else {
                $this->dbhm->preExec("INSERT INTO $table ($att) VALUES (?);" , [ $val ], FALSE, FALSE);
                $id = $this->dbhm->lastInsertId();
            }
        }

        return($id);
    }

    public function load($fn) {
        $l = new Location($this->dbhr, $this->dbhm);
        $fh = fopen($fn,'r');

        while ($row = fgets($fh)){
            $fields = str_getcsv($row);
            $pcid = $l->findByName($fields[0]);

            if (!$pcid) {
                error_log("Unknown postcode {$fields[0]}");
            } else {
                # Use REPLACE rather than INSERT as the individual fields might have changed, e.g. if a road is renamed.
                $sql = "REPLACE INTO paf_addresses (postcodeid, buildingnumber, postcodetype, suorganisationindicator, deliverypointsuffix";
                $values = [];
                $ix = 1;

                foreach ($this->idfields1 as $field) {
                    $sql .= ", {$field}id";
                    $values[] = $this->getRefId("paf_$field", $field, $fields[$ix++]);
                }

                foreach ($this->idfields2 as $field) {
                    $sql .= ", {$field}id";
                    $values[] = $this->getRefId("paf_$field", $field, $fields[$ix++]);
                }

                $sql .= ") VALUES ($pcid, " . $this->dbhm->quote($fields[6]) . ", " . $this->dbhm->quote($fields[13]) . ", " . $this->dbhm->quote($fields[14]) . ", " . $this->dbhm->quote($fields[15]);
                $ix = 0;

                foreach ($this->idfields1 as $field) {
                    $val = $values[$ix++];
                    $sql .= ", " . ($val ? $this->dbhm->quote($val) : 'NULL');
                }

                foreach ($this->idfields2 as $field) {
                    $val = $values[$ix++];
                    $sql .= ", " . ($val ? $this->dbhm->quote($val) : 'NULL');
                }

                $sql .= ");";
                $this->dbhm->preExec($sql);
            }
        }
    }

    public function listForPostcode($pc) {
        $ret = [];
        $l = new Location($this->dbhr, $this->dbhm);
        $pcid = $l->findByName($pc);

        $ids = $this->dbhr->preQuery("SELECT id FROM paf_addresses WHERE postcodeid = $pcid;");
        foreach ($ids as $id) {
            $ret[] = $id['id'];
        }

        return($ret);
    }

    public function getSingleLine($id) {
        $str = NULL;
        $a = new AllenJB\PafUtils\Address;
        $sql = "SELECT locations.name AS postcode, paf_addresses.buildingnumber";

        $fields = array_merge($this->idfields1, $this->idfields2);

        foreach ($fields as $field) {
            $sql .= ", paf_$field.$field ";
        }

        $sql .= " FROM paf_addresses LEFT JOIN locations ON locations.id = paf_addresses.postcodeid ";

        foreach ($fields as $field) {
            $sql .= " LEFT JOIN paf_$field ON paf_$field.id = paf_addresses.{$field}id ";
        }

        $sql .= " WHERE paf_addresses.id = $id";

        $addresses = $this->dbhr->preQuery($sql);
        foreach ($addresses as $address) {
            foreach ($address as $key => $val) {
                switch ($key) {
                    case 'postcode': $a->setPostcode($val); break;
                    case 'buildingnumber': $a->setBuildingNumber($val); break;
                    case 'posttown': $a->setPostTown($val); break;
                    case 'dependentlocality': $a->setDependentLocality($val); break;
                    case 'doubledependentlocality': $a->setDoubleDependentLocality($val); break;
                    case 'thoroughfaredescriptor': $a->setThoroughfare($val); break;
                    case 'dependentthoroughfaredescriptor': $a->setDependentThoroughfare($val); break;
                    case 'buildingname': $a->setBuildingName($val); break;
                    case 'subbuildingname': $a->setSubBuildingName($val); break;
                    case 'pobox': $a->setPoBox($val); break;
                    case 'departmentname': $a->setDepartmentName($val); break;
                    case 'organisationname': $a->setOrganizationName($val); break;
                }
            }

            $addr = $a->getAddressLines();

            $str = implode(', ', $addr) . ", " . $a->getPostTown() . " " . $a->getPostcode();
        }

        return($str);
    }
}