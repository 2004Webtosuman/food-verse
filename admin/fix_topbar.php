<?php
$files = glob("*.php");
$targetContentRegex = '/<header class="bg-white border-b border-card-border h-\[88px\] flex items-center justify-end px-8 z-10 hidden md:flex flex-shrink-0">\s*<a href="\.\.\/logout\.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-all">\s*<i data-lucide="power" class="w-5 h-5"><\/i>\s*<span>Logout<\/span>\s*<\/a>\s*<\/header>/s';

$replacementContent = '<header class="bg-white border-b border-card-border h-[72px] md:h-[88px] flex items-center justify-between md:justify-end px-6 md:px-8 z-10 flex-shrink-0">
                <div class="md:hidden flex items-center gap-2">
                    <h1 class="text-lg font-black italic tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                </div>
                <a href="../logout.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-all">
                    <i data-lucide="power" class="w-5 h-5"></i>
                    <span class="hidden md:inline">Logout</span>
                </a>
            </header>';

$targetContentRegex2 = '/<header\s+class="bg-white border-b border-card-border h-\[88px\] flex items-center justify-end px-8 z-10 hidden md:flex flex-shrink-0">\s*<a href="\.\.\/logout\.php"\s+class="flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-all">\s*<i data-lucide="power" class="w-5 h-5"><\/i>\s*<span>Logout<\/span>\s*<\/a>\s*<\/header>/s';

foreach($files as $f) {
    if (in_array($f, ['fix.php', 'fix_topbar.php'])) continue;
    $c = file_get_contents($f);
    
    // Replace logic
    if (preg_match($targetContentRegex, $c)) {
        $c = preg_replace($targetContentRegex, $replacementContent, $c);
        file_put_contents($f, $c);
        echo "Updated $f\n";
    } elseif (preg_match($targetContentRegex2, $c)) {
        $c = preg_replace($targetContentRegex2, $replacementContent, $c);
        file_put_contents($f, $c);
        echo "Updated $f (variant 2)\n";
    }
}
echo "Done topbar fix";
