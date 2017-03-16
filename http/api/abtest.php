<?php
function abtest() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $id = intval(presdef('id', $_REQUEST, NULL));

    $p = new Polls($dbhr, $dbhm, $id);
    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $uid = presdef('uid', $_REQUEST, NULL);
            $variants = $dbhr->preQuery("SELECT * FROM abtest WHERE uid = ? ORDER BY rate DESC;", [
                $uid
            ]);

            # We use a bandit test so that we get the benefit of the best option, while still exploring others.
            # See http://stevehanov.ca/blog/index.php?id=132 for an example description.
            $r = randomFloat();

            if ($r < 0.1) {
                # The 10% case we choose a random one of the other options.
                $s = rand(1, count($variants) - 1);
                $variant = $variants[$s];
            } else {
                # Most of the time we choose the currently best-performing option.
                $variant = $variants[0];
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'variant' => $variant
            ];
            break;
        }
        case 'POST': {
            $uid = presdef('uid', $_REQUEST, NULL);
            $variant = presdef('variant', $_REQUEST, NULL);
            $shown = array_key_exists('shown', $_REQUEST) ? filter_var($_REQUEST['shown'], FILTER_VALIDATE_BOOLEAN) : NULL;
            $action = array_key_exists('action', $_REQUEST) ? filter_var($_REQUEST['action'], FILTER_VALIDATE_BOOLEAN) : NULL;

            if ($uid && $variant) {
                if ($shown !== NULL) {
                    $sql = "INSERT INTO abtest (uid, variant, shown) VALUES (" . $dbhm->quote($uid) . ", " . $dbhm->quote($variant) . ", 1) ON DUPLICATE KEY UPDATE shown = shown + 1;";
                    $dbhm->background($sql);
                }

                if ($action !== NULL) {
                    $sql = "INSERT INTO abtest (uid, variant, action, rate) VALUES (" . $dbhm->quote($uid) . ", " . $dbhm->quote($variant) . ", 1,0) ON DUPLICATE KEY UPDATE action = action + 1, rate = 100 * action / shown;";
                    $dbhm->background($sql);
                }
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success'
            ];
            break;
        }
    }

    return($ret);
}
