<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre>";

// Check statuses
$statuses = DB::table('purchases')->distinct()->pluck('status')->toArray();
echo "STATUSES: " . implode(', ', $statuses) . "\n\n";

// Sample rows
$rows = DB::table('purchases')
    ->join('products', 'purchases.product_id', '=', 'products.id')
    ->select('purchases.id', 'products.name as product', 'purchases.quantity', 'purchases.status', 'purchases.business_id', 'purchases.branch_id')
    ->limit(10)
    ->get();

echo "SAMPLE PURCHASES:\n";
foreach ($rows as $r) {
    echo "  id={$r->id} product={$r->product} qty={$r->quantity} status={$r->status} business={$r->business_id} branch={$r->branch_id}\n";
}

// Total count
echo "\nTOTAL PURCHASES: " . DB::table('purchases')->count() . "\n";

// Low qty items (any status)
echo "\nLOW QTY ITEMS (qty <= 5, any status):\n";
$low = DB::table('purchases')
    ->join('products', 'purchases.product_id', '=', 'products.id')
    ->join('categories', 'purchases.category_id', '=', 'categories.id')
    ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
    ->select('products.name as item', 'categories.name as cat', 'branches.name as branch', 'purchases.quantity', 'purchases.status')
    ->where('purchases.quantity', '<=', 5)
    ->get();

foreach ($low as $r) {
    echo "  item={$r->item} cat={$r->cat} branch={$r->branch} qty={$r->quantity} status={$r->status}\n";
}

// Check products metadata for mfg_qty
echo "\nPRODUCT METADATA SAMPLE:\n";
$products = DB::table('products')->whereIn('type', ['finished_product','bottle_product'])->limit(5)->get(['id','name','type','metadata']);
foreach ($products as $p) {
    $meta = json_decode($p->metadata, true);
    $mfgQty = $meta['manufacturing_quantity'] ?? 'N/A';
    echo "  id={$p->id} name={$p->name} type={$p->type} mfg_qty={$mfgQty}\n";
}

echo "</pre>";
