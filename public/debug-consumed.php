<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre style='font-family:monospace;font-size:12px'>";

// Where does manufacturer record seed/material consumption?
// Check finished_goods table
$fgCols = DB::getSchemaBuilder()->getColumnListing('finished_goods');
echo "1) finished_goods columns:\n   " . implode(', ', $fgCols) . "\n\n";
$fg = DB::table('finished_goods')->limit(5)->get();
echo "2) finished_goods rows:\n";
foreach ($fg as $r) { echo "   " . json_encode($r) . "\n"; }
echo "\n";

// Check products metadata — manufacturer fills seed_qty_used / consumed fields?
$products = DB::table('products')
    ->whereIn('type', ['finished_product','bottle_product'])
    ->get(['id','name','type','metadata']);
echo "3) finished/bottle products metadata:\n";
foreach ($products as $p) {
    echo "   [{$p->id}] {$p->name} | metadata=" . $p->metadata . "\n";
}
echo "\n";

// Check stock_movements for production_consumption type
$sm = DB::table('stock_movements')
    ->join('products','stock_movements.item_id','=','products.id')
    ->whereIn('stock_movements.movement_type', ['production_consumption','material_consumption','consumption','deduction'])
    ->select('stock_movements.*','products.name as item')
    ->limit(10)->get();
echo "4) stock_movements (consumption types):\n";
if ($sm->isEmpty()) echo "   NONE\n";
foreach ($sm as $r) { echo "   id={$r->id} item={$r->item} qty={$r->quantity} dir={$r->direction} type={$r->movement_type}\n"; }
echo "\n";

// All distinct movement_types in stock_movements
$types = DB::table('stock_movements')->distinct()->pluck('movement_type')->toArray();
echo "5) All movement_types in stock_movements:\n   " . implode(', ', $types) . "\n\n";

// Check how manufacturing page saves — look at products with seed_qty_to_deduct in metadata
$allMeta = DB::table('products')
    ->whereNotNull('metadata')
    ->get(['id','name','type','metadata']);
echo "6) All products with metadata (looking for seed/consumed fields):\n";
foreach ($allMeta as $p) {
    $meta = json_decode($p->metadata, true);
    if (!empty($meta)) {
        echo "   [{$p->id}] {$p->name} type={$p->type} meta_keys=" . implode(',', array_keys($meta)) . "\n";
        foreach ($meta as $k => $v) {
            echo "       $k = " . (is_array($v) ? json_encode($v) : $v) . "\n";
        }
    }
}

echo "</pre>";
