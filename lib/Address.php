<?php

//The MIT License (MIT)
//
//Copyright (c) 2014 AllenJB
//
//Permission is hereby granted, free of charge, to any person obtaining a copy
//of this software and associated documentation files (the "Software"), to deal
//in the Software without restriction, including without limitation the rights
//to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
//copies of the Software, and to permit persons to whom the Software is
//furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all
//copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
//IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
//FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
//AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
//LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
//OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
//SOFTWARE.
//


namespace AllenJB\PafUtils;

class Address
{

    protected $udprn = null;

    protected $postcode = null;

    protected $postTown = null;

    protected $dependentLocality = null;

    protected $doubleDependentLocality = null;

    protected $thoroughfare = null;

    protected $dependentThoroughfare = null;

    protected $buildingNumber = null;

    protected $buildingName = null;

    protected $subBuildingName = null;

    protected $poBox = null;

    protected $departmentName = null;

    protected $organizationName = null;

    protected $postcodeType = null;

    protected $suOrganizationIndicator = null;

    protected $deliveryPointSuffix = null;

    protected $addressLines = null;

    protected $assembleDebugFlags = array();


    public function __construct()
    {
    }


    /**
     * @return null
     */
    public function getUdprn()
    {
        return $this->udprn;
    }


    /**
     * @param null $udprn
     * @return self
     */
    public function setUdprn($udprn)
    {
        $this->udprn = $udprn;
        return $this;
    }


    /**
     * @return null
     */
    public function getPostcode()
    {
        return $this->postcode;
    }


    /**
     * @param null $postcode
     * @return self
     */
    public function setPostcode($postcode)
    {
        $this->addressLines = null;
        $this->postcode = $postcode;
        return $this;
    }


    /**
     * @return null
     */
    public function getPostTown()
    {
        return $this->postTown;
    }


    /**
     * @param null $postTown
     * @return self
     */
    public function setPostTown($postTown)
    {
        $this->addressLines = null;
        $this->postTown = $postTown;
        return $this;
    }


    /**
     * @return null
     */
    public function getDependentLocality()
    {
        return $this->dependentLocality;
    }


    /**
     * @param null $dependentLocality
     * @return self
     */
    public function setDependentLocality($dependentLocality)
    {
        $this->addressLines = null;
        $this->dependentLocality = $dependentLocality;
        return $this;
    }


    /**
     * @return null
     */
    public function getDoubleDependentLocality()
    {
        return $this->doubleDependentLocality;
    }


    /**
     * @param null $doubleDependentLocality
     * @return self
     */
    public function setDoubleDependentLocality($doubleDependentLocality)
    {
        $this->addressLines = null;
        $this->doubleDependentLocality = $doubleDependentLocality;
        return $this;
    }


    /**
     * @return null
     */
    public function getThoroughfare()
    {
        return $this->thoroughfare;
    }


    /**
     * @param null $thoroughfare
     * @return self
     */
    public function setThoroughfare($thoroughfare)
    {
        $this->addressLines = null;
        $this->thoroughfare = $thoroughfare;
        return $this;
    }


    /**
     * @return null
     */
    public function getDependentThoroughfare()
    {
        return $this->dependentThoroughfare;
    }


    /**
     * @param null $dependentThoroughfare
     * @return self
     */
    public function setDependentThoroughfare($dependentThoroughfare)
    {
        $this->addressLines = null;
        $this->dependentThoroughfare = $dependentThoroughfare;
        return $this;
    }


    /**
     * @return null
     */
    public function getBuildingNumber()
    {
        return $this->buildingNumber;
    }


    /**
     * @param null $buildingNumber
     * @return self
     */
    public function setBuildingNumber($buildingNumber)
    {
        $this->addressLines = null;
        $this->buildingNumber = $buildingNumber;
        return $this;
    }


    /**
     * @return null
     */
    public function getBuildingName()
    {
        return $this->buildingName;
    }


    /**
     * @param null $buildingName
     * @return self
     */
    public function setBuildingName($buildingName)
    {
        $this->addressLines = null;
        $this->buildingName = $buildingName;
        return $this;
    }


    /**
     * @return null
     */
    public function getSubBuildingName()
    {
        return $this->subBuildingName;
    }


    /**
     * @param null $subBuildingName
     * @return self
     */
    public function setSubBuildingName($subBuildingName)
    {
        $this->addressLines = null;
        $this->subBuildingName = $subBuildingName;
        return $this;
    }


    /**
     * @return null
     */
    public function getPobox()
    {
        return $this->poBox;
    }


    /**
     * @param null $poBox
     * @return self
     */
    public function setPoBox($poBox)
    {
        $this->addressLines = null;
        $this->poBox = $poBox;
        return $this;
    }


    /**
     * @return null
     */
    public function getDepartmentName()
    {
        return $this->departmentName;
    }


    /**
     * @param null $departmentName
     * @return self
     */
    public function setDepartmentName($departmentName)
    {
        $this->addressLines = null;
        $this->departmentName = $departmentName;
        return $this;
    }


    /**
     * @return null
     */
    public function getOrganizationName()
    {
        return $this->organizationName;
    }


    /**
     * @param null $organizationName
     * @return self
     */
    public function setOrganizationName($organizationName)
    {
        $this->addressLines = null;
        $this->organizationName = $organizationName;
        return $this;
    }


    /**
     * @return null
     */
    public function getPostcodeType()
    {
        return $this->postcodeType;
    }


    /**
     * @param null $postcodeType
     * @return self
     */
    public function setPostcodeType($postcodeType)
    {
        $this->postcodeType = $postcodeType;
        return $this;
    }


    /**
     * @return null
     */
    public function getSuOrganizationIndicator()
    {
        return $this->suOrganizationIndicator;
    }


    /**
     * @param null $suOrganizationIndicator
     * @return self
     */
    public function setSuOrganizationIndicator($suOrganizationIndicator)
    {
        $this->suOrganizationIndicator = $suOrganizationIndicator;
        return $this;
    }


    /**
     * @return null
     */
    public function getDeliveryPointSuffix()
    {
        return $this->deliveryPointSuffix;
    }


    /**
     * @param null $deliveryPointSuffix
     * @return self
     */
    public function setDeliveryPointSuffix($deliveryPointSuffix)
    {
        $this->deliveryPointSuffix = $deliveryPointSuffix;
        return $this;
    }


    /**
     * @return null
     */
    public function getAddressLines()
    {
        if ($this->addressLines === null) {
            $this->assembleAddressLines();
        }
        return $this->addressLines;
    }


    /**
     * Assemble the address lines (excluding post town and post code) according to the rule laid out by Royal Mail.
     * The primary reference for these rules is the Royal Mail programmers guide. In some cases (particularly those
     * not covered by the programmers guide), the layout used by the RM online address finder has been followed.
     *
     * @fixme Simplify whitespace handling with $nextLinePrefix
     */
    protected function assembleAddressLines()
    {
        $this->assembleDebugFlags = array('errors' => array());
        $processed = false;
        $processingError = false;
        $addressLines = array();

        // Take copies of the building name and number
        // This allows us to manipulate their values (specifically for the split building name rules)
        // without affecting the object "true" values, while making later processing within this method simpler
        $buildingName = $this->buildingName;
        $buildingNumber = $this->buildingNumber;
        if ($buildingNumber == 0) {
            $buildingNumber = null;
        }

        // Exception 4 regex: Any of the specified prefixes followed by either a number with an alpha suffix or a numeric range
        $specialPrefixes = array(
            'Back of',
            'Block',
            'Blocks',
            'Building',
            'Maisonette',
            'Maisonettes',
            'Rear of',
            'Shop',
            'Shops',
            'Stall',
            'Stalls',
            'Suite',
            'Suites',
            'Unit',
            'Units',
        );
        $ex4Regex = '/^(' . join('|', $specialPrefixes) . ')\s([0-9]+[a-zA-Z]+|[0-9]+\-[0-9]+|[a-zA-Z])$/';

        if (strlen($buildingName)) {
            if (preg_match($ex4Regex, $buildingName)) {
                $this->assembleDebugFlags['ex4BuildingName'] = true;
            }
        }

        if (strlen($this->subBuildingName)) {
            if (preg_match($ex4Regex, $this->subBuildingName)) {
                $this->assembleDebugFlags['ex4SubBuildingName'] = true;
            }
        }

        // Do we need to split the building name - see Table 27c / 27d
        if (! empty($buildingName) && empty($buildingNumber)) {

            if (preg_match('/\s[0-9]+[a-zA-Z]+$/', $buildingName)
                || preg_match('/\s[0-9]+\-[0-9]+$/', $buildingName)
            ) {
                if (! preg_match($ex4Regex, $buildingName)) {
                    $this->assembleDebugFlags['splitBuildingName'] = true;
                    $parts = explode(' ', $buildingName);
                    $buildingNumber = array_pop($parts);
                    $buildingName = join(' ', $parts);
                }
            }
        }


        // Table 19, note b: If an organization name is present, it should appear on the first address line
        if (! empty($this->organizationName)) {
            $addressLines[] = $this->organizationName;
        }
        // Table 19, note c: If a department name is present, it should appear on the second line (after organization name)
        if (! empty($this->departmentName)) {
            $addressLines[] = $this->departmentName;
        }
        // Table 19, note d: If a PO Box is present, it should appear on the first address line after any organization / dept. name
        // The PO Box number MUST be preceded by 'PO Box'
        if (! empty($this->poBox)) {
            $addressLines[] = 'PO Box ' . $this->poBox;
        }

        $nextLinePrefix = null;

        // Rule 1 - Organisation name only
        if (empty($this->subBuildingName) && empty($buildingName) && empty($buildingNumber)) {
            if (!empty($this->organizationName)) {
                $processed = true;
                $this->assembleDebugFlags['rule'] = 1;

                // No actual manipulation code as the organization name is handled above
            }
        }

        // The following code is based on Table 20
        // Rule 2 - Building number only
        if (empty($this->subBuildingName) && empty($buildingName) && (! empty($buildingNumber))) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 2;

            $this->assembleDebugFlags['nlpBuildingNumber'] = true;
            $nextLinePrefix = $buildingNumber . ' ';
        }

        // Rule 3 - Building Name only
        if (empty($this->subBuildingName) && (! empty($buildingName)) && empty($buildingNumber)) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 3;

            // Exceptions:
            // i) First and last characters of the building name are numeric (eg. '1to1' or '100:1')
            // ii) First and penultimate characters are numeric, last character is alphabetic
            // iii) Building name has only 1 character
            if (preg_match('/^[0-9].*[0-9]$/', $buildingName)
                || preg_match('/^[0-9].*[0-9][a-zA-Z]$/', $buildingName)
                || (strlen($buildingName) == 1)
            ) {
                $this->assembleDebugFlags['exceptionBuildingName'] = true;
                if ($nextLinePrefix !== null) {
                    $this->assembleDebugFlags['errors'][] = 'NLP ' . __LINE__;
                    $processingError = true;
                }

                $this->assembleDebugFlags['nlpBuildingName'] = true;
                $nextLinePrefix = $buildingName;
                if ((strlen($buildingName) == 1) && (! is_numeric($buildingName))) {
                    $nextLinePrefix .= ',';
                }
                $nextLinePrefix .= ' ';
            } else {
                $addressLines[] = $buildingName;
            }
        }

        // Rule 4 - Building Name & Building Number
        if (empty($this->subBuildingName) && (! empty($buildingName) && (! empty($buildingNumber)))) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 4;

            $this->assembleDebugFlags['nlpBuildingNumber'] = true;
            $addressLines[] = $buildingName;
            $nextLinePrefix = $buildingNumber . ' ';
        }

        // Rule 5 - Sub building name & Building Number
        // The programmers guide talks about an exception involving the 'concatenation indicator',
        // But as far as I can see this field doesn't exist in the CSV format files
        if ((! empty($this->subBuildingName) && empty($buildingName) && (! empty($buildingNumber)))) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 5;

            $this->assembleDebugFlags['nlpBuildingNumber'] = true;
            $addressLines[] = $this->subBuildingName;
            $nextLinePrefix = $buildingNumber . ' ';
        }

        // Rule 6 - Sub Building Name & Building Name
        if ((! empty($this->subBuildingName)) && (! empty($buildingName)) && empty($buildingNumber)) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 6;

            $exceptionSubBuildingName = false;
            // Exceptions:
            // i) First and last characters of the building name are numeric (eg. '1to1' or '100:1')
            // ii) First and penultimate characters are numeric, last character is alphabetic
            // iii) Building name has only 1 character
            if (preg_match('/^[0-9].*[0-9]$/', $this->subBuildingName)
                || preg_match('/^[0-9].*[0-9][a-zA-Z]$/', $this->subBuildingName)
                || (strlen($this->subBuildingName) == 1)
            ) {
                $this->assembleDebugFlags['exceptionSubBuildingName'] = true;
                $exceptionSubBuildingName = true;

                if ($nextLinePrefix !== null) {
                    $this->assembleDebugFlags['errors'][] = 'NLP ' . __LINE__;
                    $processingError = true;
                }

                $this->assembleDebugFlags['nlpSubBuildingName'] = true;
                $nextLinePrefix = $this->subBuildingName;
                if ((strlen($this->subBuildingName) == 1) && (! is_numeric($this->subBuildingName))) {
                    $nextLinePrefix .= ',';
                }
                $nextLinePrefix .= ' ';
            } else {
                $addressLines[] = $this->subBuildingName;
            }


            // Exceptions:
            // i) First and last characters of the building name are numeric (eg. '1to1' or '100:1')
            // ii) First and penultimate characters are numeric, last character is alphabetic
            // iii) Building name has only 1 character
            if (preg_match('/^[0-9].*[0-9]$/', $buildingName)
                || preg_match('/^[0-9].*[0-9][a-zA-Z]$/', $buildingName)
                || (strlen($buildingName) == 1)
            ) {
                $this->assembleDebugFlags['exceptionBuildingName'] = true;

                if ((strlen($nextLinePrefix)) && (!$exceptionSubBuildingName)) {
                    $addressLines[] = trim($nextLinePrefix);
                    $nextLinePrefix = null;
                }

                $this->assembleDebugFlags['nlpBuildingName'] = true;
                $nextLinePrefix = trim(trim($nextLinePrefix) .' '. $buildingName);
                if ((strlen($buildingName) == 1) && (! is_numeric($buildingName))) {
                    $nextLinePrefix .= ',';
                }
                $nextLinePrefix .= ' ';
            } else {
                $addressLines[] = (strlen($nextLinePrefix) ? trim($nextLinePrefix) . ' ' : '') . $buildingName;
                $nextLinePrefix = null;
            }
        }

        // Rule 7 - Sub building name, building name & building number
        if (! (empty($this->subBuildingName) || empty($buildingName) || (empty($buildingNumber)))) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 7;

            // Exceptions:
            // i) First and last characters of the building name are numeric (eg. '1to1' or '100:1')
            // ii) First and penultimate characters are numeric, last character is alphabetic
            // iii) Building name has only 1 character
            if (preg_match('/^[0-9].*[0-9]$/', $this->subBuildingName)
                || preg_match('/^[0-9].*[0-9][a-zA-Z]$/', $this->subBuildingName)
                || (strlen($this->subBuildingName) == 1)
            ) {
                $this->assembleDebugFlags['exceptionSubBuildingName'] = true;
                if ($nextLinePrefix !== null) {
                    $this->assembleDebugFlags['errors'][] = 'NLP ' . __LINE__;
                    $processingError = true;
                }

                $this->assembleDebugFlags['nlpSubBuildingName'] = true;
                $nextLinePrefix = $this->subBuildingName;
                if ((strlen($this->subBuildingName) == 1) && (! is_numeric($this->subBuildingName))) {
                    $nextLinePrefix .= ',';
                }
                $nextLinePrefix .= ' ';
            } else {
                $addressLines[] = (strlen($nextLinePrefix) ? trim($nextLinePrefix) . ' ' : '') . $this->subBuildingName;
                $nextLinePrefix = null;
            }

            $addressLines[] = (strlen($nextLinePrefix) ? trim($nextLinePrefix) . ' ' : '') . $buildingName;
            $nextLinePrefix = null;

            $this->assembleDebugFlags['nlpBuildingNumber'] = true;
            $nextLinePrefix = (strlen($nextLinePrefix) ? trim($nextLinePrefix) . ' ' : '') . $buildingNumber;
        }


        // Rule C1 - Self-written rule: Sub-building name occurs, but no building name or number
        // This occurred in the Y14M09 update - 8350793 / EH12 5DD (15Gf Eglinton Crescent)
        // And was still the same on the Royal Mail Postcode Lookup website data as of 2014-10-21
        if (empty($buildingName) && empty($buildingNumber) && (! empty($this->subBuildingName))) {
            $processed = true;
            $this->assembleDebugFlags['rule'] = 'c1';
            $nextLinePrefix = $this->subBuildingName;

            // FIXME Should we test the exception rules?
        }

        if (strlen($nextLinePrefix) && (substr($nextLinePrefix, -1) != ' ')) {
            $nextLinePrefix .= ' ';
        }

        // Dependent Thoroughfare
        if (! empty($this->dependentThoroughfare)) {
            $addressLines[] = (strlen($nextLinePrefix) ? $nextLinePrefix : '') . $this->dependentThoroughfare;
            $nextLinePrefix = null;
        }
        // Thoroughfare
        if (! empty($this->thoroughfare)) {
            $addressLines[] = (strlen($nextLinePrefix) ? $nextLinePrefix : '') . $this->thoroughfare;
            $nextLinePrefix = null;
        }
        // Double dependent locality
        if (! empty($this->doubleDependentLocality)) {
            $addressLines[] = (strlen($nextLinePrefix) ? $nextLinePrefix : '') . $this->doubleDependentLocality;
            $nextLinePrefix = null;
        }
        // Dependent locality
        if (! empty($this->dependentLocality)) {
            $addressLines[] = (strlen($nextLinePrefix) ? $nextLinePrefix : '') . $this->dependentLocality;
            $nextLinePrefix = null;
        }

        // Yup, apparently there's addresses in the database with no locality / thoroughfare. Just a number.
        // UDPRNs affected as of 2014-02-06: 2431986 and 328392
        if ($nextLinePrefix !== null) {
            $this->assembleDebugFlags['nlpAlone'] = true;
            $addressLines[] = $nextLinePrefix;
        }

        $this->assembleDebugFlags['processingError'] = $processingError;
        $this->assembleDebugFlags['processed'] = $processed;

        // Send a notification to developers if an address was not processed by any of the above rules
        // (and should have been) or if we think there was an error in processing the address correctly
        // (because the RM programmers guide does not specify how to correctly handle the address encountered)
        if ((! $processed) || $processingError) {
            if (! (empty($this->subBuildingName) && empty($buildingName) && (empty($buildingNumber)))) {
                if ($processingError) {
                    trigger_error("An address (with UDPRN: {$this->udprn}) was probably processed incorrectly",
                        E_USER_NOTICE);
                } else {
                    trigger_error("An address (with UDPRN: {$this->udprn}) was not processed by any rules",
                        E_USER_NOTICE);
                }
            }
        }

        $this->addressLines = $addressLines;
    }


    /**
     * Return the debug flags that indicate what processing was done by assembleAddressLines()
     * While this is internal implementation information, it can be useful, for example, to build an SQL table
     * containing these values to allow quick location of addresses that are matching certain rules / criteria
     *
     * @return array
     */
    public function getAssemblyDebugFlags()
    {
        return $this->assembleDebugFlags;
    }
}
