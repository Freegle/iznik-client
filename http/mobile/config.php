<?php
header('Content-Type: application/json');
$config = array(
    'disabled' => 1,
    'startupinfo' => "Sorry, this app cannot be used for the time being. Please use <a href='https://ilovefreegle.org/' class='ext-link'>www.ilovefreegle.org</a> until a replacement app is available."
);
echo json_encode($config);
