<?php
require 'bootstrap/app.php';
$app = $GLOBALS['app'];
$app->make('Illuminate\Contracts\Http\Kernel')->handle(Illuminate\Http\Request::capture());

$products = DB::table('products')
    ->whereIn('name', ['Tukur Azmud Soap', 'Bulug Al-maram', 'Rozmerry', 'Ashiq al-Oud Perfume 1', 'Torshiin', 'Jelebiya turku'])
    ->get(['id', 'name', 'type', 'status', 'selling_price']);

foreach ($products as $p) {
    echo sprintf("%-30s | Type: %-20s | Status: %-10s | Price: %s\n", 
        $p->name, $p->type, $p->status, $p->selling_price);
}

// Also check stock movements for these products
echo "\n\n=== Stock Movements ===\n";
$stockQuery = DB::table('stock_movements')
    ->whereIn('item_id', $products->pluck('id'))
    ->where('item_type', 'product')
    ->selectRaw('item_id, SUM(CASE WHEN direction="in" THEN quantity ELSE -quantity END) as total_qty')
    ->groupBy('item_id')
    ->get();

$productMap = $products->keyBy('id');
foreach ($stockQuery as $s) {
    $product = $productMap->get($s->item_id);
    if ($product) {
        echo sprintf("%-30s | Total Stock: %s\n", $product->name, $s->total_qty);
    }
}
