<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');

use Pheanstalk\Pheanstalk;

# For gracefully restarting the background processing; signal to it.
$pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);

$id = $pheanstalk->put(json_encode(array(
    'type' => 'exit'
)));
