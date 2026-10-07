<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre style='font-family:monospace;font-size:12px'>";

// Check products with selling_price
$prods = DB::table('products')
    ->select('id','name','selling_price','cost_price','type','status')
    ->get();
echo "Products:\n";
foreach ($prods as $p) {
    echo "  [{$p->id}] {$p->name} | type={$p->type} | selling={$p->selling_price} | cost={$p->cost_price} | status={$p->status}\n";
}
echo "\n";

// Check stock_movements for products
$sm = DB::table('stock_movements')
    ->join('products', 'stock_movements.item_id', '=', 'products.id')
    ->where('stock_movements.item_type', 'product')
    ->select('products.id','products.name','products.selling_price',
             DB::raw("SUM(CASE WHEN direction='in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as qty"))
    ->groupBy('products.id','products.name','products.selling_price')
    ->get();

echo "Available qty per product (from stock_movements):\n";
foreach ($sm as $r) {
    $val = (float)$r->qty * (float)$r->selling_price;
    echo "  [{$r->id}] {$r->name} | qty={$r->qty} | selling={$r->selling_price} | value={$val}\n";
}
echo "\nTotal selling price = " . $sm->filter(fn($r) => $r->qty > 0)->sum(fn($r) => $r->qty * $r->selling_price) . "\n";

// Also check purchases qty
echo "\nPurchases available qty:\n";
$pur = DB::table('purchases')
    ->join('products','purchases.product_id','=','products.id')
    ->where('purchases.status','completed')
    ->select('products.id','products.name','products.selling_price',
             DB::raw('SUM(purchases.quantity) as total_qty'))
    ->groupBy('products.id','products.name','products.selling_price')
    ->get();
foreach ($pur as $r) {
    $val = (float)$r->total_qty * (float)$r->selling_price;
    echo "  [{$r->id}] {$r->name} | qty={$r->total_qty} | selling={$r->selling_price} | value={$val}\n";
}
echo "\nPurchases-based total selling = " . $pur->sum(fn($r) => $r->total_qty * $r->selling_price) . "\n";

echo "</pre>";
