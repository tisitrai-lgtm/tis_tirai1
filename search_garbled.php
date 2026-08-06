<?php
$files = ["user_page copy.php", "user_page.php", "user_register_water_money.php"];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    echo "=== $file ===\n";
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (preg_match('/[喔喙]/u', $line)) {
            echo ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
