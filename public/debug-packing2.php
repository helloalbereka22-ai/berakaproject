<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre style='font-family:monospace;font-size:12px'>";

// Check mashagiya product and category types exactly
$rows = DB::table('purchases')
    ->join('products',   'purchases.product_id',  '=', 'products.id')
    ->join('categories', 'purchases.category_id', '=', 'categories.id')
    ->whereIn('purchases.purchase_type', ['raw_material', 'packing_material'])
    ->select(
        'products.id', 'products.name as item', 'products.type as product_type',
        'categories.name as cat_name', 'categories.type as cat_type',
        'purchases.quantity', 'purchases.purchase_type'
    )
    ->get();

echo "Raw material purchases with product.type and category.type:\n\n";
foreach ($rows as $r) {
    echo "[{$r->id}] {$r->item}\n";
    echo "   product.type    = {$r->product_type}\n";
    echo "   category.name   = {$r->cat_name}\n";
    echo "   category.type   = {$r->cat_type}\n";
    echo "   purchase.type   = {$r->purchase_type}\n";
    echo "   quantity        = {$r->quantity}\n\n";
}

// Total manufacturing_quantity from all finished products
$total = DB::table('products')
    ->whereIn('type', ['finished_product','bottle_product'])
    ->whereNotNull('metadata')
    ->get(['name','metadata'])
    ->sum(function($p) {
        $meta = json_decode($p->metadata, true);
        return (float)($meta['manufacturing_quantity'] ?? 0);
    });
echo "Total manufacturing_quantity across all finished products: $total\n";

echo "</pre>";
