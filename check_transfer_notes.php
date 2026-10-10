<?php
/**
 * Check what notes exist on transfer purchases
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TRANSFER PURCHASE NOTES ===\n\n";

$transfers = DB::table('purchases')
    ->where('status', 'completed')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->where(function ($q) {
        $q->where('notes', 'like', 'Transfer%')
          ->orWhere('notes', 'like', '%Transfer%');
    })
    ->select('id', 'notes', 'quantity', 'product_id', 'branch_id', 'purchase_date')
    ->get();

echo "Found " . count($transfers) . " purchases with Transfer in notes:\n\n";

foreach ($transfers as $t) {
    echo "ID: {$t->id}\n";
    echo "  Notes: {$t->notes}\n";
    echo "  Qty: {$t->quantity}\n";
    echo "  Date: {$t->purchase_date}\n";
    echo "\n";
}

// Also check raw_material and packing_material purchases
echo "\n=== ALL RAW MATERIAL & PACKING PURCHASES ===\n\n";

$all = DB::table('purchases')
    ->where('status', 'completed')
    ->whereIn('purchase_type', ['raw_material', 'packing_material'])
    ->select('id', 'product_id', 'notes', 'quantity', 'purchase_type')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($all as $p) {
    echo "ID: {$p->id} | Type: {$p->purchase_type} | Qty: {$p->quantity}\n";
    echo "  Notes: " . (empty($p->notes) ? '(empty)' : substr($p->notes, 0, 80)) . "\n";
}
