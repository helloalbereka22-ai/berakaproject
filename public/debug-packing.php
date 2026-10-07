<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre style='font-family:monospace;font-size:12px'>";

// Show all finished product metadata in full
$products = DB::table('products')
    ->whereIn('type', ['finished_product','bottle_product'])
    ->whereNotNull('metadata')
    ->get(['id','name','type','metadata']);

echo "All finished products metadata:\n\n";
foreach ($products as $p) {
    $meta = json_decode($p->metadata, true) ?? [];
    echo "[{$p->id}] {$p->name}\n";
    foreach ($meta as $k => $v) {
        if ($k === 'images') continue;
        echo "   $k = " . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
    echo "\n";
}

// Check if packing_item_id / packing_name / packing_qty_used stored anywhere
echo "\nSearching metadata keys for packing-related fields:\n";
foreach ($products as $p) {
    $meta = json_decode($p->metadata, true) ?? [];
    foreach ($meta as $k => $v) {
        if (stripos($k, 'pack') !== false || stripos($k, 'bottle') !== false || stripos($k, 'item') !== false) {
            echo "[{$p->id}] {$p->name} | $k = $v\n";
        }
    }
}

echo "</pre>";
