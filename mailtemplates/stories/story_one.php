<?php

function story_one($groupname, $headline, $story, $hr = TRUE) {
    $html = '<h3>' . htmlspecialchars(trim($headline)) . '</h3>' .
        '<span style="color: gray">From a freegler on ' . htmlspecialchars($groupname) . '</span><br />' .
        '<p>' . nl2br(trim($story)) . '</p>';

    if ($hr) {
        $html .= '<span style="color: gray"><hr></span>';
    }

    return($html);
}
