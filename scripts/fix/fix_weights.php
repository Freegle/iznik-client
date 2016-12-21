<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/message/Item.php');

$items = $dbhr->preQuery("SELECT * FROM items ORDER BY popularity DESC;");

foreach ($items as $item) {
    $i = new Item($dbhr, $dbhm, $item['id']);
    $weight = $i->estimateWeight();
    $i->setWeight($weight);
    error_log("{$item['name']} = $weight");
}
