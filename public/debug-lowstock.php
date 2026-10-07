<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='font-family:monospace;font-size:13px'>";

// 1. Total stock_movements
$total = DB::table('stock_movements')->count();
echo "1) Total stock_movements rows: $total\n\n";

// 2. item_types
$types = DB::table('stock_movements')->distinct()->pluck('item_type')->toArray();
echo "2) item_types: " . implode(', ', $types) . "\n\n";

// 3. Net qty per product from stock_movements (with branch)
$sm = DB::table('stock_movements')
    ->join('products', 'stock_movements.item_id', '=', 'products.id')
    ->leftJoin('branches', 'stock_movements.branch_id', '=', 'branches.id')
    ->where('stock_movements.item_type', 'product')
    ->select([
        'products.name as item',
        'branches.name as branch',
        DB::raw("SUM(CASE WHEN stock_movements.direction='in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as net_qty"),
    ])
    ->groupBy('stock_movements.item_id', 'stock_movements.branch_id', 'products.name', 'branches.name')
    ->orderBy('net_qty')
    ->get();

echo "3) Net qty per product+branch (stock_movements):\n";
if ($sm->isEmpty()) {
    echo "   *** NO ROWS — stock_movements is empty or no item_type='product' rows ***\n";
} else {
    foreach ($sm as $r) {
        echo "   item={$r->item} | branch={$r->branch} | net_qty={$r->net_qty}\n";
    }
}
echo "\n";

// 4. Dashboard method — no branch grouping, just item_id
$dash = DB::table('stock_movements')
    ->join('products', 'stock_movements.item_id', '=', 'products.id')
    ->where('stock_movements.item_type', 'product')
    ->select([
        'products.name as item',
        DB::raw("SUM(CASE WHEN stock_movements.direction='in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as qty"),
    ])
    ->groupBy('stock_movements.item_id', 'products.name')
    ->orderBy('qty')
    ->get();

echo "4) Dashboard net qty per product (no branch split):\n";
if ($dash->isEmpty()) {
    echo "   *** EMPTY ***\n";
} else {
    foreach ($dash as $r) {
        echo "   item={$r->item} | qty={$r->qty}\n";
    }
}
echo "\n";

// 5. purchases table sample
$pur = DB::table('purchases')
    ->join('products', 'purchases.product_id', '=', 'products.id')
    ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
    ->select(['products.name as item', 'branches.name as branch', 'purchases.quantity', 'purchases.status'])
    ->orderBy('purchases.quantity')
    ->limit(15)
    ->get();

echo "5) purchases sample (qty ASC):\n";
foreach ($pur as $r) {
    echo "   item={$r->item} | branch={$r->branch} | qty={$r->quantity} | status={$r->status}\n";
}

echo "\n";
echo "6) business_ids on stock_movements: " . implode(', ', DB::table('stock_movements')->distinct()->pluck('business_id')->toArray()) . "\n";
echo "7) business_ids on purchases: "       . implode(', ', DB::table('purchases')->distinct()->pluck('business_id')->toArray()) . "\n";

echo "</pre>";
