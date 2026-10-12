<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get bilal user
$bilal = \App\Models\User::where('email', 'bilal@example.com')->orWhere('name', 'bilal')->first();
if (!$bilal) {
    $bilal = \App\Models\User::find(2); // Try ID 2
}

if (!$bilal) {
    echo "ERROR: Could not find bilal user\n";
    exit(1);
}

echo "=== BILAL USER ===\n";
echo "ID: {$bilal->id}, Name: {$bilal->name}, Email: {$bilal->email}\n";
echo "Assigned Branch IDs: " . json_encode($bilal->assignedBranchIds()) . "\n";
echo "Assigned Business IDs: " . json_encode($bilal->assignedBusinessIds()) . "\n\n";

// Get the branch
$branchIds = $bilal->assignedBranchIds();
$branchId = !empty($branchIds) ? $branchIds[0] : null;

if ($branchId) {
    $branch = \App\Models\Branch::find($branchId);
    echo "=== BILAL'S BRANCH ===\n";
    echo "ID: {$branch->id}, Name: {$branch->name}\n\n";
}

// Check today's products
$today = now()->toDateString();
echo "=== TODAY'S DATE: $today ===\n\n";

$products = \App\Models\Product::where('status', 'active')->whereNotNull('metadata')->get();

echo "=== ALL PRODUCTS WITH MANUFACTURING DATA ===\n";
$count = 0;
$totalQty = 0;

foreach ($products as $p) {
    $meta = $p->metadata ?? [];
    
    // Skip if no manufacturing data
    if (empty($meta['manufacturing_added_at'])) continue;
    
    try {
        $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
    } catch (\Exception $e) {
        continue;
    }
    
    // Check if today
    if ($addedDate !== $today) continue;
    
    $count++;
    echo "\n[$count] PRODUCT: {$p->name}\n";
    echo "    ID: {$p->id}\n";
    echo "    manufacturing_added_at: {$meta['manufacturing_added_at']}\n";
    echo "    last_mfr_quantity: " . ($meta['last_mfr_quantity'] ?? 'N/A') . "\n";
    echo "    manufacturing_quantity: " . ($meta['manufacturing_quantity'] ?? 'N/A') . "\n";
    echo "    manufactured_by_branch_id: " . ($meta['manufactured_by_branch_id'] ?? 'N/A') . "\n";
    echo "    manufacturing_daily_qty_by_branch: " . json_encode($meta['manufacturing_daily_qty_by_branch'] ?? []) . "\n";
    
    $lastMfr = (float) ($meta['last_mfr_quantity'] ?? 0);
    $totalQty += $lastMfr;
    
    // Check if this product is visible to bilal
    $manufacturingByBranchId = (int) ($meta['manufactured_by_branch_id'] ?? 0);
    $isVisibleToBilal = (int)$branchId === $manufacturingByBranchId;
    echo "    ✓ Visible to bilal: " . ($isVisibleToBilal ? 'YES' : 'NO') . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total products with today's manufacturing: $count\n";
echo "Sum of last_mfr_quantity: $totalQty\n";
echo "Expected (from table): 35 (12+23)\n";
echo "Actual (KPI): 50\n";
echo "Match: " . ($totalQty == 35 ? 'YES ✓' : 'NO ✗ (' . ($totalQty == 50 ? 'Got 50 instead' : 'Got ' . $totalQty . ' instead') . ')') . "\n";
