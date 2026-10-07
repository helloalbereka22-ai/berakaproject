<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='font-family:monospace;font-size:12px'>";

// 1. purchases for raw/packing materials
$rows = DB::table('purchases')
    ->join('products', 'purchases.product_id', '=', 'products.id')
    ->join('categories', 'purchases.category_id', '=', 'categories.id')
    ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
    ->whereIn('purchases.purchase_type', ['raw_material', 'packing_material'])
    ->where('purchases.status', 'completed')
    ->select([
        'products.id as product_id',
        'products.name as item',
        'categories.name as cat',
        'branches.name as branch',
        'purchases.quantity',
        'purchases.unit_price',
        'purchases.total_price',
        'purchases.purchase_date',
    ])
    ->orderBy('products.name')
    ->get();

echo "1) PURCHASES (raw/packing, completed):\n";
foreach ($rows as $r) {
    echo "   [{$r->product_id}] {$r->item} | cat={$r->cat} | branch={$r->branch} | qty={$r->quantity} | unit_price={$r->unit_price} | total_price={$r->total_price} | date={$r->purchase_date}\n";
}
echo "\n";

// 2. batch_material_consumption columns
$cols = DB::getSchemaBuilder()->getColumnListing('batch_material_consumption');
echo "2) batch_material_consumption columns:\n   " . implode(', ', $cols) . "\n\n";

// 3. sample batch_material_consumption
$bmc = DB::table('batch_material_consumption')
    ->leftJoin('products', 'batch_material_consumption.product_id', '=', 'products.id')
    ->select('batch_material_consumption.*', 'products.name as product_name')
    ->limit(10)->get();
echo "3) sample batch_material_consumption:\n";
foreach ($bmc as $r) {
    echo "   " . json_encode($r) . "\n";
}
echo "\n";

// 4. SUM consumed per product
$consumed = DB::table('batch_material_consumption')
    ->join('products', 'batch_material_consumption.product_id', '=', 'products.id')
    ->select('products.id', 'products.name', DB::raw('SUM(batch_material_consumption.quantity_used) as total_consumed'))
    ->groupBy('products.id', 'products.name')
    ->get();
echo "4) Consumed qty per product (batch_material_consumption):\n";
foreach ($consumed as $r) {
    echo "   [{$r->id}] {$r->name} | consumed={$r->total_consumed}\n";
}

echo "</pre>";
