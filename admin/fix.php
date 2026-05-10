<?php
$files = glob("*.php");
foreach($files as $f) {
    if (in_array($f, ['reports.php', 'categories.php'])) continue;
    $c = file_get_contents($f);
    $c = preg_replace('/href="#"\s+class="([^"]*)">\s*<i data-lucide="tags"/m', 'href="categories.php" class="$1">' . "\n" . '                    <i data-lucide="tags"', $c);
    $c = preg_replace('/href="#"\s+class="([^"]*)">\s*<i data-lucide="bar-chart-2"/m', 'href="reports.php" class="$1">' . "\n" . '                    <i data-lucide="bar-chart-2"', $c);
    file_put_contents($f, $c);
}
echo "Done";
