<?php

function relevant_one($subject, $href) {
    $html = <<<EOT
<a href="$href">$subject</a><br />
EOT;
    return($html);
}
