<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='font-family:monospace;font-size:12px'>";

// 1. purchases columns
$cols = DB::getSchemaBuilder()->getColumnListing('purchases');
echo "1) purchases columns:\n   " . implode(', ', $cols) . "\n\n";

// 2. all raw/packing purchases
$rows = DB::table('purchases')
    ->join('products', 'purchases.product_id', '=', 'products.id')
    ->leftJoin('categories', 'purchases.category_id', '=', 'categories.id')
    ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
    ->whereIn('purchases.purchase_type', ['raw_material', 'packing_material'])
    ->select('products.id as pid', 'products.name as item', 'categories.name as cat',
             'branches.name as branch', 'purchases.quantity', 'purchases.unit_price',
             'purchases.total_price', 'purchases.status', 'purchases.purchase_date')
    ->orderBy('products.name')
    ->get();

echo "2) All raw/packing purchases:\n";
foreach ($rows as $r) {
    echo "   [{$r->pid}] {$r->item} | {$r->cat} | {$r->branch} | qty={$r->quantity} | unit_price={$r->unit_price} | total={$r->total_price} | status={$r->status} | date={$r->purchase_date}\n";
}
echo "\n";

// 3. batch_material_consumption — all rows with raw_material_id
$bmc = DB::table('batch_material_consumption')
    ->leftJoin('products', 'batch_material_consumption.raw_material_id', '=', 'products.id')
    ->select('batch_material_consumption.*', 'products.name as mat_name')
    ->get();

echo "3) batch_material_consumption rows:\n";
if ($bmc->isEmpty()) {
    echo "   *** EMPTY — no consumption records ***\n";
} else {
    foreach ($bmc as $r) {
        echo "   id={$r->id} | mat=[{$r->raw_material_id}]{$r->mat_name} | qty_used={$r->quantity_used} | qty_consumed={$r->quantity_consumed} | batch={$r->production_batch_id}\n";
    }
}
echo "\n";

// 4. Where manufacturer fills consumed qty — check production_batches
$pbCols = DB::getSchemaBuilder()->getColumnListing('production_batches');
echo "4) production_batches columns:\n   " . implode(', ', $pbCols) . "\n\n";

$pb = DB::table('production_batches')->limit(3)->get();
echo "5) Sample production_batches:\n";
foreach ($pb as $r) { echo "   " . json_encode($r) . "\n"; }
echo "\n";

// 6. Check manufacturing_orders
$moCols = DB::getSchemaBuilder()->getColumnListing('manufacturing_orders');
echo "6) manufacturing_orders columns:\n   " . implode(', ', $moCols) . "\n\n";

$mo = DB::table('manufacturing_orders')->limit(3)->get();
echo "7) Sample manufacturing_orders:\n";
foreach ($mo as $r) { echo "   " . json_encode($r) . "\n"; }

echo "</pre>";
