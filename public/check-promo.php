<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";

// Check all settings
echo "=== ALL SETTINGS ===\n";
$all = DB::table('settings')->get(['key','value']);
foreach ($all as $s) {
    echo $s->key . " = " . $s->value . "\n";
}

// Check promo files
echo "\n=== PROMO FILES IN STORAGE ===\n";
$dirs = ['promo', 'system', 'products'];
foreach ($dirs as $d) {
    $path = __DIR__ . '/../storage/app/public/' . $d . '/';
    if (is_dir($path)) {
        echo "\nstorage/app/public/$d/:\n";
        foreach (scandir($path) as $f) {
            if ($f !== '.' && $f !== '..') echo "  - $f\n";
        }
    }
}

echo "</pre>";

// Also show images directly
echo "<h2 style='font-family:Arial;'>Images found in storage/promo:</h2>";
$path = __DIR__ . '/../storage/app/public/promo/';
if (is_dir($path)) {
    foreach (scandir($path) as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "<img src='/albareka/public/storage/promo/$f' style='width:200px;height:120px;object-fit:cover;margin:5px;border:2px solid green;'>";
        }
    }
} else {
    echo "<p style='color:red;font-family:Arial;'>No promo directory found!</p>";
}
