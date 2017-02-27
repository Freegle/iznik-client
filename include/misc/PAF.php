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

    private $cache = [];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    private function getRefId($table, $att, $val) {
        # Get an id from the appropriate table, inserting a new value if required.
        $id = NULL;

        if ($val && strlen(trim($val))) {
            if (!array_key_exists($table, $this->cache)) {
                $this->cache[$table] = [];
            }

            if (pres($val, $this->cache[$table])) {
                $id = $this->cache[$table][$val];
                #error_log("Got cached $val for $att = $val in $table");
            } else {
                $vals = $this->dbhr->preQuery("SELECT id FROM $table WHERE $att = ?;", [ $val ], FALSE, FALSE);

                if (count($vals) > 0) {
                    $id = $vals[0]['id'];
                } else {
                    $this->dbhm->preExec("INSERT INTO $table ($att) VALUES (?);" , [ $val ], NULL, FALSE);
                    $id = $this->dbhm->lastInsertId();
                }

                $this->cache[$table][$val] = $id;
            }
        }

        return($id);
    }

    public function load($fn, $foutpref) {
        error_log("Input $fn, output to $foutpref");
        $l = new Location($this->dbhr, $this->dbhm);
        $fh = fopen($fn,'r');
        $count = 0;
        $unknowns = [];
        $csvfile = 0;
        $fhout = NULL;

        while ($row = fgets($fh)){
            # We generate CSV files so that we can use LOAD DATA INFILE.
            if ($count % 100000 == 0) {
                $fname = $foutpref . sprintf('%010d', $csvfile++) . ".csv";
                $fhout = fopen($fname, "w");

                # Clear cache to keep memory sane.
                $this->cache = [];
            }

            $fields = str_getcsv($row);
            $pcid = $l->findByName($fields[0]);

            if (!$pcid) {
                if (!in_array($fields[0], $unknowns)) {
                    error_log("Unknown postcode {$fields[0]}");
                    $unknowns[] = $fields[0];
                }
            } else {
                $csv = [ $pcid, $fields[12], $fields[6], $fields[13], $fields[14], $fields[15] ];

                $ix = 1;

                foreach ($this->idfields1 as $field) {
                    $csv[] = $this->getRefId("paf_$field", $field, $fields[$ix++]);
                }

                foreach ($this->idfields2 as $field) {
                    $csv[] = $this->getRefId("paf_$field", $field, $fields[$ix++]);
                }

                fputcsv($fhout, $csv);
            }

            $count++;

            if ($count % 1000 === 0) { error_log("...$count"); }
        }

        error_log("Unknown " . var_export($unknowns, TRUE));
    }

    public function update($fn) {
        $l = new Location($this->dbhr, $this->dbhm);
        $fh = fopen($fn,'r');
        $count = 0;
        $differs = 0;

        while ($row = fgets($fh)){
            # Parse the line.
            $fields = str_getcsv($row);
            $postcode = $l->findByName($fields[0]);
            $udprn = $fields[12];
            $buildingnumber = $fields[6];
            $postcodetype = $fields[13];
            $suorganisationindicator = $fields[14];
            $deliverypointsuffix = $fields[15];

            $ix = 1;

            foreach ($this->idfields1 as $field) {
                $$field = $this->getRefId("paf_$field", $field, $fields[$ix++]);
            }

            foreach ($this->idfields2 as $field) {
                $$field = $this->getRefId("paf_$field", $field, $fields[$ix++]);
            }

            $addresses = $this->dbhr->preQuery("SELECT * FROM paf_addresses WHERE udprn = ?;", [ $udprn ]);
            foreach ($addresses as $address) {
                # Compare the values
                foreach ($address as $key => $val) {
                    if (!is_int($key) && $key != 'id') {
                        $v = str_replace('id', '', $key);
                        $$v = $$v == ' ' ? NULL : $$v;
                        $$v = $$v == '' ? NULL : $$v;

                        if ($val != $$v) {
                            error_log("UDPRN $udprn differs in $key $val => {$$v}");
                            $differs++;
                            $this->dbhm->preExec("UPDATE paf_addresses SET $key = ? WHERE id = ?;", [
                                $$v,
                                $address['id']
                            ]);
                        }
                    }
                }
            }

            if (count($addresses) === 0) {
                # This is a new entry.
                $sql = "INSERT INTO paf_addresses (postcodeid, buildingnumber, postcodetype, suorganisationindicator, deliverypointsuffix";
                $values = [];
                $ix = 1;

                foreach ($this->idfields1 as $field) {
                    $sql .= ", {$field}id";
                    $v = $$field;
                    $v = $v == ' ' ? NULL : $v;
                    $v = $v == '' ? NULL : $v;

                    $values[] = $v;
                }

                foreach ($this->idfields2 as $field) {
                    $sql .= ", {$field}id";
                    $v = $$field;
                    $v = $v == ' ' ? NULL : $v;
                    $v = $v == '' ? NULL : $v;

                    $values[] = $v;
                }

                $sql .= ") VALUES ($postcode, " . $this->dbhm->quote($buildingnumber) . ", " . $this->dbhm->quote($postcodetype) . ", " . $this->dbhm->quote($suorganisationindicator) . ", " . $this->dbhm->quote($deliverypointsuffix);
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
                $differs++;
                $this->dbhm->preExec($sql);
            }

            $count++;

            if ($count % 1000 === 0) { error_log("...$count"); }
        }

        return($differs);
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

    public function listForPostcodeId($pcid) {
        $ret = [];

        $ids = $this->dbhr->preQuery("SELECT id FROM paf_addresses WHERE postcodeid = $pcid;");
        foreach ($ids as $id) {
            $ret[] = $id['id'];
        }

        return($ret);
    }

    public function getSingleLine($id) {
        return($this->getFormatted($id, ', '));
    }

    public function getFormatted($id, $delimiter) {
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

            $str = implode($delimiter, $addr) . $delimiter . $a->getPostTown() . " " . $address['postcode'];
        }

        return($str);
    }
}