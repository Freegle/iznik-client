<?php

# Run on backup server to recover a modconfig from a backup to the live system.
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

$dsn = "mysql:host=localhost;port=3309;dbname=iznik;charset=utf8";
$dbhback = new LoggedPDO($dsn, SQLUSER, SQLPASSWORD, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$opts = getopt('i:');

if (count($opts) < 1) {
    echo "Usage: hhvm modconfig_restore.php -i <modconfig id>\n";
} else {
    $id = $opts['i'];

    $cback = new ModConfig($dbhback, $dbhback, $id);
    $clive = new ModConfig($dbhr, $dbhm);
    
    if ($cback->getId() == $id) {
        error_log("Found config in backup");
        
        # Create a live one.
        $lid = $clive->create($cback->getPrivate('name'), $cback->getPrivate('createdby'));
        error_log("Created new config $lid");
        
        # Copy the attributes
        foreach ($cback->publicatts as $att) {
            if ($att != 'id' && $att != 'name' && $att != 'createdby') {
                $clive->setPrivate($att, $cback->getPrivate($att));
            }
        }

        # Now copy the existing standard messages.  Doing it this way will preserve any custom order.
        $stdmsgs = $cback->getPublic()['stdmsgs'];
        $order = [];
        foreach ($stdmsgs as $stdmsg) {
            error_log("...stdmsg {$stdmsg['id']} {$stdmsg['title']}");
            $sfrom = new StdMessage($dbhback, $dbhback, $stdmsg['id']);
            $atts = $sfrom->getPublic();
            $sto = new StdMessage($dbhr, $dbhm);
            $sid = $sto->create($atts['title'], $lid);
            unset($atts['id']);
            unset($atts['title']);
            unset($atts['configid']);

            foreach ($atts as $att => $val) {
                $sto->setPrivate($att, $val);
            }

            $order[] = $sid;
        }

        $clive->setPrivate('messageorder', json_encode($order, true));

        # Now copy the existing bulk ops
        $bulkops = $cback->getPublic()['bulkops'];
        foreach ($bulkops as $bulkop) {
            error_log("...bulkop {$bulkop['id']} {$bulkop['title']}");
            $bfrom = new BulkOp($dbhback, $dbhback, $bulkop['id']);
            $atts = $bfrom->getPublic();
            $bto = new BulkOp($dbhr, $dbhm);
            $bid = $bto->create($atts['title'], $lid);
            unset($atts['id']);
            unset($atts['title']);
            unset($atts['configid']);

            foreach ($atts as $att => $val) {
                $bto->setPrivate($att, $val);
            }
        }
    }
}
