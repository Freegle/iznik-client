<?php
define('IZNIK_BASE', dirname(__FILE__) . '/..');
require_once('/etc/iznik.conf');
require_once(IZNIK_BASE . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

# First the top-level static pages.
$tops = [
    'give' => 1,
    'find' => 1,
    'explore' => 0.8,
    'about' => 0.1,
    'terms' => 0.1,
    'privacy' => 0.1,
    'donate' => 0.3,
    'contact' => 0.5
];

$prot = presdef('HTTPS', $_SERVER) ? 'https://' : 'http://';

foreach ($tops as $top => $prio) {
    echo "<url><loc>$prot" . USER_SITE . "/$top</loc><changefreq>monthly</changefreq><priority>$prio</priority></url>\n";
}

# Now the groups.
$groups = $dbhr->preQuery("SELECT id, nameshort FROM groups WHERE type = 'Freegle' AND publish = 1 AND onhere = 1;");
foreach ($groups as $group) {
    echo "<url><loc>$prot" . USER_SITE . "/explore/{$group['nameshort']}</loc><changefreq>hourly</changefreq></url>\n";
}
?>
</urlset>
