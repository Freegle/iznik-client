<?php
define('SQLLOG', FALSE);

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');

error_log("Get users");
$count = 0;
do {
    $dbhm->preExec("delete from users_images where url IS NOT NULL AND url like 'https://www.gravatar.com/avatar%' AND url like '%wavatar%' limit 1000;", NULL, FALSE);
    $aff = $dbhm->rowsAffected();
    $count+= $aff;
    error_log($count);
} while ($aff > 0);
