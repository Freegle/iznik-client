<?php
function supporters() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $s = new Supporters($dbhr, $dbhm);

        $ret = [
            'ret' => 0,
            'status' => 'Success',
            'supporters' => $s->get()
        ];
    }

    return($ret);
}
