<?php
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
global $dbhr, $dbhm;
require_once(IZNIK_BASE . '/include/utils.php');

$opts = getopt('s:e:r:i:o:l:');

function createVertex(&$graph, &$vertices, $id, $label, $depth, $info, $timestamp) {
    if (!pres($id, $vertices)) {
        #error_log("Create $id depth $depth label $label");
        $vertices[$id] = $graph->createVertex($id);

        $vertices[$id]->setAttribute('graphviz.label', $label . "\n\n$info\n");

//        $vertices[$id]->setAttribute('graphviz.style', 'filled');
        $vertices[$id]->setAttribute('graphviz.colorscheme', 'gnbu9');
//        $vertices[$id]->setAttribute('graphviz.color', 9-$depth);
//        $vertices[$id]->setAttribute('graphviz.fillcolor', 9-$depth);
        return($vertices[$id]);
    } else {
        $t = $vertices[$id]->getAttribute('graphviz.label');

        if (strlen($t) < 256) {
            # Keep the labels a reasonable length.
            $vertices[$id]->setAttribute('graphviz.label', "$t$info\n");
        }
        return($vertices[$id]);
    }
}

function drawGraph($graph) {
    error_log("Draw graph...");
    $graphviz = new Graphp\GraphViz\GraphViz();
    $graphviz->display($graph);

    $path = "/tmp";

    $latest_ctime = 0;
    $latest_filename = '';

    $d = dir($path);
    while (false !== ($entry = $d->read())) {
        $filepath = "{$path}/{$entry}";
        // could do also other checks than just checking whether the entry is a file
        if (is_file($filepath) && strpos($filepath, "png") !== FALSE && filectime($filepath) > $latest_ctime) {
            $latest_ctime = filectime($filepath);
            $latest_filename = $entry;
        }
    }

    header('Content-Type: image/png');
    return($path . "/" . $latest_filename);
}

function addWeightedEdge($lastvert, $vert) {
    # See if there is already an edge between these two.
    $edges = $lastvert->getEdgesTo($vert);
    $got = false;

    foreach ($edges as $edge) {
        $got = true;
        $label = $edge->getAttribute('graphviz.label');
        $label++;
        $edge->setAttribute('graphviz.label', $label);
        $edge->setAttribute('graphviz.weight', $label);

        # Increase the prominence of the destination vertex.
        $w = $vert->getAttribute('graphviz.penwidth');
        $w = $w ? $w+1 : $w;
        $vert->setAttribute('graphviz.penwidth', $w);
    }

    if (!$got) {
        $edge = $lastvert->createEdgeTo($vert);
        $edge->setAttribute('graphviz.label', 1);
    }

    # Reset the colors
    $edges = $lastvert->getEdges();
    $maxedge = null;
    $maxweight = 0;

    foreach ($edges as $edge) {
        $edge->setAttribute('graphviz.color', 'black');
        $w = intval($edge->getAttribute('graphviz.label'));

        if ($w > $maxweight) {
            $maxedge = $edge;
            $maxweight = $w;
        }
    }

    $maxedge->setAttribute('graphviz.color', 'red');
    $maxedge->setAttribute('graphviz.width', 10);
}

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

function canonRoute($route) {
    $route = preg_replace('/\?$/', '', $route);

    # Replace bits of URLs that vary.
    $route = preg_replace('/\/message\/.*/', 'message/{{id}}', $route);
    $route = preg_replace('/\/groups\/.*/', 'group/{{name}}', $route);
    $route = preg_replace('/\/explore\/.*/', 'explore/{{name}}', $route);
    $route = preg_replace('/\/chat\/.*/', 'chat/{{id}}', $route);
    $route = preg_replace('/\/search\/.*/', '/search/{{term}}', $route);

    if (substr($route, 0, 1) === '/' && strlen($route) > 1) {
        $route = substr($route, 1);
    }

    return($route);
}

function canonEvent($event) {
    $event = preg_replace('/\?u=(.*)\&/', '?u={{uid}}&', $event);
    $event = preg_replace('/\?u=(.*)$/', '?u={{uid}}', $event);

    return($event);
}

if (count($opts) < 2) {
    echo "Usage: hhvm web_graph.php -s <start date/time> -e <end date-time> (-r <route filter> -i <ip address> -o <0 = logged out, 1 = logged in>)\n";
} else {
    $starttime = date ("Y-m-d H:i:s", strtotime($opts['s']));
    $endtime = date ("Y-m-d H:i:s", strtotime($opts['e']));
    $filt = pres('r', $opts) ? $opts['r'] : null;
    $filtr = $filt ? (" AND route LIKE " . $dbhr->quote("%$filt%")) : '';
    $ip = pres('i', $opts) ? $opts['i'] : null;
    $ipr = $ip ? (" AND ip LIKE " . $dbhr->quote("%$ip%")) : '';
    $loggedout = pres('o', $opts) ? $opts['o'] : 1;
    $limit = presdef('l', $opts, NULL);
    $lstr = $limit ? " LIMIT $limit " : "";

    # Find the distinct IPs.
    error_log("Find distinct IPs...");
    $logq = $loggedout ? " AND userid IS NULL " : "AND userid IS NOT NULL ";
    $sql = "SELECT DISTINCT ip, route FROM logs_events WHERE timestamp >= '$starttime' AND timestamp <= '$endtime' AND ip IS NOT NULL $filtr $ipr $logq $lstr;";
    #error_log($sql);
    $ips = $dbhr->preQuery($sql);
    $vertices = array();

    $graph = new Fhaculty\Graph\Graph();
    $start = createVertex($graph, $vertices, 0, count($ips) . " sessions", 0, NULL, NULL);
    $lastvert = $start;

    foreach ($ips as $ip) {
        $depth = 1;

        # Add a vertex for the entry point.
        $r = canonRoute($ip['route']);
        error_log("Entry $r");
        $lastvert = createVertex($graph, $vertices, "$r-$depth", $r , $depth++, $ip['ip'], NULL);
        addWeightedEdge($start, $lastvert);

        # Find the routes and actions they went through, excluding dull ones.
        $sql = "SELECT timestamp, route, target, event, viewx, viewy FROM logs_events WHERE timestamp >= '$starttime' AND timestamp <= '$endtime' && ip = '{$ip['ip']}' AND event NOT IN ('keydown', 'mouseout', 'focus', 'focusout', 'mousemove', 'scroll') AND target NOT LIKE '%:default' $filtr $logq ORDER BY timestamp ASC;";
        $routes = $dbhr->preQuery($sql);
        $laststamp = $starttime;
        $lastkey = null;

        foreach ($routes as $route) {
            $key = canonRoute($route['route']);

            if ($lastkey == null || $key != $lastkey) {
                $lastkey = $key;
                error_log("{$ip['ip']} {$route['timestamp']} $key");

                if ($laststamp) {
                    $thisstamp = strtotime($route['timestamp']);
                    #error_log("$thisstamp vs $laststamp");

                    if ($thisstamp - $laststamp > 300) {
                        #error_log("Gap " . ($thisstamp - $laststamp) . " - reset");
                        $depth = 1;
                    }
                }

                $laststamp = strtotime($route['timestamp']);
                if ($route['target'] == 'route') {
                    $evstr = canonRoute($route['route']);
                } else {
                    $evstr = canonEvent($route['event']);
                }
                $vert = createVertex($graph, $vertices, "$key-$depth", $evstr, $depth++, "{$ip['ip']} {$route['viewx']}x{$route['viewy']}", $route['timestamp']);
                addWeightedEdge($lastvert, $vert);

                $lastvert = $vert;
            }
        }
    }

    # Draw it.
    $fn = drawGraph($graph);
    copy($fn, "../../http/graph.png");
}

