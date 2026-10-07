<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre style='font-family:monospace;font-size:12px'>";

// Show ALL metadata keys for finished/bottle products
$products = DB::table('products')
    ->whereIn('type', ['finished_product', 'bottle_product'])
    ->whereNotNull('metadata')
    ->get(['id','name','type','metadata']);

echo "Finished/bottle products — full metadata:\n\n";
foreach ($products as $p) {
    $meta = json_decode($p->metadata, true) ?? [];
    echo "[{$p->id}] {$p->name} (type={$p->type})\n";
    foreach ($meta as $k => $v) {
        if ($k === 'images') continue;
        echo "   $k = " . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
    echo "\n";
}

// Also check manufacturing page source code for what it saves to metadata
// Look for bottle_item, packing, bottle_qty etc in manufacturing Livewire
echo "\n--- Checking manufacturing products page for bottle/packing fields ---\n";
$files = glob(base_path('app/Livewire/Admin/Manufacturing/*.php'));
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (stripos($content, 'bottle') !== false || stripos($content, 'packing') !== false) {
        // Extract relevant lines
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (stripos($line, 'bottle') !== false || stripos($line, 'packing') !== false
                || stripos($line, 'metadata') !== false) {
                echo basename($f) . ":" . ($i+1) . "  " . trim($line) . "\n";
            }
        }
    }
}

echo "</pre>";
