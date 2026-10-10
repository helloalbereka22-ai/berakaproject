<?php
/**
 * Test the purchase filter logic
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING PURCHASE FILTER ===\n\n";

// Show ALL raw material and packing material purchases
echo "ALL raw_material and packing_material purchases:\n";
$all = DB::table('purchases')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->where('status', 'completed')
    ->orderBy('id', 'desc')
    ->get(['id', 'product_id', 'quantity', 'notes', 'purchase_date']);

foreach ($all as $p) {
    $product = DB::table('products')->where('id', $p->product_id)->value('name');
    echo "ID: {$p->id} | Qty: {$p->quantity} | Product: {$product}\n";
    echo "  Notes: " . ($p->notes ? "'{$p->notes}'" : "NULL") . "\n";
}

echo "\n\nFiltered (excluding transfers):\n";
$filtered = DB::table('purchases')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->where('status', 'completed')
    ->whereRaw("(notes IS NULL OR (notes NOT LIKE 'Transfer from%' AND notes NOT LIKE 'from_branch:%'))")
    ->orderBy('id', 'desc')
    ->get(['id', 'product_id', 'quantity', 'notes']);

foreach ($filtered as $p) {
    $product = DB::table('products')->where('id', $p->product_id)->value('name');
    echo "ID: {$p->id} | Qty: {$p->quantity} | Product: {$product}\n";
    echo "  Notes: " . ($p->notes ? "'{$p->notes}'" : "NULL") . "\n";
}

echo "\n\nCount comparison:\n";
echo "Total: " . count($all) . "\n";
echo "Filtered: " . count($filtered) . "\n";
echo "Excluded: " . (count($all) - count($filtered)) . "\n";
