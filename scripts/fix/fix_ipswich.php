<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$msgs = $dbhr->preQuery("select date, subject from messages inner join messages_groups on messages.id = messages_groups.msgid and groupid = 21483 where date < '2016-10-01' and msgtype = 'Offer' order by date asc;");

$parsed = 0;
$toplevelpostcode = 0;
$fullpostcode = 0;
$areaandtop = 0;
$areaaonly = 0;
$locations = [];
$unmatched = [];

function addLoc($loc) {
    global $locations;
    $loc = strtolower($loc);
    $loc = str_replace(',', '', $loc);
    if (!array_key_exists($loc, $locations)) {
        $locations[$loc] = 0;
    }

    $locations[$loc]++;
}

foreach ($msgs as $msg) {
    print "{$msg['subject']} ";
    $subj = $msg['subject'];
    $subj = str_replace('[IpswichRecycle] ', '', $subj);

    if (preg_match('/.*\((.*)\).*/', $subj)) {
        $parsed++;

        if (preg_match('/.*\((IP\d*)\).*/i', $subj, $matches)) {
            # Top-level postcode.
            print "...top postcode {$matches[1]}\n";
            $toplevelpostcode++;
        } else if (preg_match('/.*\((IP\d* \d.*)\).*/i', $subj, $matches)) {
            print "...full postcode {$matches[1]}\n";
            $fullpostcode++;
        } else if (preg_match('/.*\((IP\d*) (.*)\).*/i', $subj, $matches)) {
            print "...top postcode {$matches[1]} + area {$matches[2]}\n";
            $areaandtop++;
            addLoc($matches[2]);
        } else if (preg_match('/.*\((.*) (IP\d*)\).*/i', $subj, $matches)) {
            print "...area {$matches[1]} + top postcode {$matches[2]}\n";
            $areaandtop++;
            addLoc($matches[1]);
        } else {
            $unmatched[] = $subj;
        }
    } else {
        print "...can't parse";
    }


    print "\n";
}

# Just areas we have identified.
print "Scan for known areas only\n.";
$remaining = [];
foreach ($unmatched as $subj) {
    $matched = FALSE;

    foreach ($locations as $area => $count) {
        if (stripos($subj, "($area)") !== FALSE) {
            print "Found $area in $subj " . stripos($subj, $area) . "\n";
            $areaaonly++;
            $locations[$area]++;
            $matched = TRUE;
        }
    }

    if (!$matched) {
        $remaining[] = $subj;
    }
}
$unmatched = $remaining;

print "\n\nTotal posts " . count($msgs) . " parsed $parsed\n\nTypes of locations:\n\nTop-level postcode only: $toplevelpostcode\nFull postcode only: $fullpostcode\nArea + Top-level postcode: $areaandtop\nArea only: $areaaonly\nUnmatched: " . count($unmatched);

arsort($locations);
$totalarearefs = 0;
foreach ($locations as $area => $count) {
    $totalarearefs += $count;
}

print "\n\n" . count($locations) . " areas with $totalarearefs references: ";

foreach ($locations as $loc => $count) {
    print " $loc ($count),";
}

print "\n\n";

print "Unmatched subjects:\n";
foreach ($unmatched as $subj) {
    print "$subj\n";
}