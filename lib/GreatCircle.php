<?php

class GreatCircle
{

    /*
     * Find a point a certain distance and vector away from an initial point
     * converted from c function found at: http://sam.ucsd.edu/sio210/propseawater/ppsw_c/gcdist.c
     * and retrieved from http://stackoverflow.com/questions/772878/great-circle-distance-question
     *
     * @param int distance in meters
     * @param double direction in degrees i.e. 0 = North, 90 = East, etc.
     * @param double lon starting longitude
     * @param double lat starting latitude
     * @return array ('lon' => $lon, 'lat' => $lat)
     */
    public static function getPositionByDistance($distance, $direction, $lat, $lon)
    {
        if (floatval($distance) == 0) {
            return array('lng' => $lon, 'lat' => $lat);
        }

        $metersPerDegree = 111120.00071117;
        $degreesPerMeter = 1.0 / $metersPerDegree;
        $radiansPerDegree = pi() / 180.0;
        $degreesPerRadian = 180.0 / pi();

        if ($distance > $metersPerDegree*180)
        {
            $direction -= 180.0;
            if ($direction < 0.0)
            {
                $direction += 360.0;
            }
            $distance = $metersPerDegree * 360.0 - $distance;
        }

        if ($direction > 180.0)
        {
            $direction -= 360.0;
        }

        $c = $direction * $radiansPerDegree;
        $d = $distance * $degreesPerMeter * $radiansPerDegree;
        $L1 = $lat * $radiansPerDegree;
        $lon *= $radiansPerDegree;
        $coL1 = (90.0 - $lat) * $radiansPerDegree;
        $coL2 = self::ahav(self::hav($c) / (self::sec($L1) * self::csc($d)) + self::hav($d - $coL1));
        $L2   = (pi() / 2) - $coL2;
        $l    = $L2 - $L1;

        $dLo = (cos($L1) * cos($L2));
        if ($dLo != 0.0)
        {
            $dLo  = self::ahav((self::hav($d) - self::hav($l)) / $dLo);
        }

        if ($c < 0.0)
        {
            $dLo = -$dLo;
        }

        $lon += $dLo;
        if ($lon < -pi())
        {
            $lon += 2 * pi();
        }
        elseif ($lon > pi())
        {
            $lon -= 2 * pi();
        }

        $xlat = $L2 * $degreesPerRadian;
        $xlon = $lon * $degreesPerRadian;

        return array('lng' => $xlon, 'lat' => $xlat);
    }


    /*
     * copy the sign
     */
    private static function copysign($x, $y)
    {
        return ((($y) < 0.0) ? - abs($x) : abs($x));
    }

    /*
     * not greater than 1
     */
    private static function ngt1($x)
    {
        return (abs($x) > 1.0 ? self::copysign(1.0 , $x) : ($x));
    }

    /*
     * haversine
     */
    private static function hav($x)
    {
        return ((1.0 - cos($x)) * 0.5);
    }

    /*
     * arc haversine
     */
    private static function ahav($x)
    {
        return acos(self::ngt1(1.0 - ($x * 2.0)));
    }

    /*
     * secant
     */
    private static function sec($x)
    {
        return (1.0 / cos($x));
    }

    /*
     * cosecant
     */
    private static function csc($x)
    {
        return (1.0 / sin($x));
    }

    public static function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earth_radius = 6371*1000;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;

        return $d;
    }
}