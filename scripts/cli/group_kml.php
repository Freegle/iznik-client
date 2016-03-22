<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/lib/geoPHP/geoPHP.inc');

$sql = "SELECT id, nameshort, poly FROM groups WHERE type = 'Freegle' AND poly IS NOT NULL AND publish = 1 ORDER BY nameshort ASc;";
$groups = $dbhr->preQuery($sql);

$kml = "<?xml version='1.0' encoding='UTF-8'?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
    <Document>
        <name>Freegle Derived Areas</name>
        <description><![CDATA[Areas derived from analysis of where people post.]]></description>
        <Folder>
            <name>Groups</name>";

foreach ($groups as $group) {
    error_log("Group {$group['nameshort']}");
    $geom = geoPHP::load($group['poly'], 'wkt');
    $kml .= "<Placemark><name>{$group['nameshort']}</name>" . $geom->out('kml') . "</Placemark>\r\n";
}

$kml .= "		</Folder>
	</Document>
</kml>\r\n";

echo $kml;
