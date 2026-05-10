<?php
$files = glob("*.php");

foreach($files as $f) {
    if (in_array($f, ['fix.php', 'fix_topbar.php', 'fix_name.php'])) continue;
    $c = file_get_contents($f);
    
    // Replace FOODVERSE with FOODVERSE
    $c = str_replace('FOODVERSE', 'FOODVERSE', $c);
    $c = str_replace('FOOD<br>EXPRESS', 'FOODVERSE', $c);
    
    // Replace FoodVerse with FoodVerse in title
    $c = str_replace('FoodVerse', 'FoodVerse', $c);
    
    // Remove italic from the h1 class
    $c = str_replace('italic ', '', $c);
    
    // In mobile topbar, we had text-lg, let's make it text-2xl
    $c = str_replace('text-lg font-black tracking-tighter uppercase text-primary leading-tight', 'text-2xl font-black tracking-tighter uppercase text-primary leading-tight', $c);
    
    file_put_contents($f, $c);
}
echo "Done fixing brand name";
