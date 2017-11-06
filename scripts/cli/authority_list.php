<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');

$sql = "SELECT id, name, AsText(st_simplify(polygon, 0.001)) AS polygon FROM authorities WHERE level = 'Primary' ORDER BY name ASC;";
$authoritys = $dbhr->preQuery($sql);

$kml = "<?xml version='1.0' encoding='UTF-8'?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
    <Document>
        <name>Primary Authority Areas</name>
        <description><![CDATA[Hopefully]]></description>
        <Folder>
            <name>Authorities</name>";

foreach ($authoritys as $authority) {
    $geom = geoPHP::load($authority['polygon'], 'wkt');
    $kml .= "<Placemark><name>{$authority['name']}</name>" . $geom->out('kml') . "</Placemark>\r\n";
}

$kml .= "		</Folder>
	</Document>
</kml>\r\n";

echo $kml;
