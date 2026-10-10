<?php
/**
 * Debug script: Check user branch assignments
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== USER BRANCH ASSIGNMENTS ===\n\n";

// Get bilal user
$bilal = User::where('email', 'hebesha@gmail.com')->first();
if ($bilal) {
    echo "User: bilal (hebesha@gmail.com)\n";
    echo "  ID: {$bilal->id}\n";
    echo "  Assigned Business IDs: " . json_encode($bilal->assignedBusinessIds()) . "\n";
    echo "  Assigned Branch IDs: " . json_encode($bilal->assignedBranchIds()) . "\n";
    
    // Get branch details
    $branches = DB::table('branches')
        ->whereIn('id', $bilal->assignedBranchIds())
        ->get(['id', 'name', 'business_id']);
    echo "  Branch Details:\n";
    foreach ($branches as $b) {
        echo "    - Branch {$b->id}: {$b->name} (Business {$b->business_id})\n";
    }
}

echo "\n";

// Get manufacurer user
$mfr = User::where('email', 'manf@gmail.com')->first();
if ($mfr) {
    echo "User: manufacurer (manf@gmail.com)\n";
    echo "  ID: {$mfr->id}\n";
    echo "  Assigned Business IDs: " . json_encode($mfr->assignedBusinessIds()) . "\n";
    echo "  Assigned Branch IDs: " . json_encode($mfr->assignedBranchIds()) . "\n";
    
    // Get branch details
    $branches = DB::table('branches')
        ->whereIn('id', $mfr->assignedBranchIds())
        ->get(['id', 'name', 'business_id']);
    echo "  Branch Details:\n";
    foreach ($branches as $b) {
        echo "    - Branch {$b->id}: {$b->name} (Business {$b->business_id})\n";
    }
}

echo "\n=== STOCK MOVEMENTS BY BRANCH ===\n\n";

// Check what branches have stock movements for a sample product
$sampleProduct = DB::table('products')
    ->where('name', '100 ml Bottle')
    ->first();

if ($sampleProduct) {
    echo "Product: {$sampleProduct->name} (ID: {$sampleProduct->id})\n";
    
    $movements = DB::table('stock_movements')
        ->where('item_id', $sampleProduct->id)
        ->where('item_type', 'product')
        ->get(['branch_id', 'business_id', 'direction', 'quantity', 'moved_at']);
    
    echo "Stock Movements for this product:\n";
    $byBranch = [];
    foreach ($movements as $m) {
        $byBranch[$m->branch_id] = ($byBranch[$m->branch_id] ?? 0) + ($m->direction === 'in' ? $m->quantity : -$m->quantity);
        echo "  Branch {$m->branch_id}: {$m->direction} {$m->quantity} (Business {$m->business_id})\n";
    }
    echo "\nNet by branch:\n";
    foreach ($byBranch as $branchId => $qty) {
        $branchName = DB::table('branches')->where('id', $branchId)->value('name');
        echo "  Branch {$branchId} ({$branchName}): {$qty}\n";
    }
}
