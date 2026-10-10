<?php
/**
 * Debug: Check actual purchase notes to understand transfer format
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ALL PURCHASES WITH RAW_MATERIAL OR PACKING_MATERIAL TYPE ===\n\n";

$purchases = DB::table('purchases')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->where('status', 'completed')
    ->orderBy('id', 'desc')
    ->get(['id', 'product_id', 'branch_id', 'quantity', 'purchase_date', 'notes']);

foreach ($purchases as $p) {
    $product = DB::table('products')->where('id', $p->product_id)->value('name');
    $branch = DB::table('branches')->where('id', $p->branch_id)->value('name');
    
    echo "ID: {$p->id} | Product: {$product} | Branch: {$branch} | Qty: {$p->quantity}\n";
    echo "  Notes: '" . ($p->notes ?? 'NULL') . "'\n";
    echo "  Date: {$p->purchase_date}\n";
    echo "\n";
}

echo "\n=== CHECKING FILTER LOGIC ===\n\n";

// Test the filter
$withFilter = DB::table('purchases')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->where('status', 'completed')
    ->where(function ($q) {
        $q->whereNull('notes')
          ->orWhere('notes', 'NOT LIKE', '%Transfer from%');
    })
    ->count();

$withoutFilter = DB::table('purchases')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->where('status', 'completed')
    ->count();

echo "Total purchases (no filter): {$withoutFilter}\n";
echo "Purchases after filter: {$withFilter}\n";
echo "Filtered out: " . ($withoutFilter - $withFilter) . "\n";
