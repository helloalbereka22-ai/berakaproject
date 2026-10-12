<?php

// Quick diagnostic to identify phantom manufacturing units
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;

echo "=== Manufacturing KPI Diagnostic ===\n";
$today = now()->toDateString();
echo "Today: $today\n\n";

// Get bilal user for manufacturer role check
$bilal = User::where('email', 'bilal@test.com')->first();
if (!$bilal) {
    echo "ERROR: Could not find bilal user\n";
    exit(1);
}

// Set auth context
auth()->setUser($bilal);

$userBranchIds = $bilal->assignedBranchIds();
echo "Bilal's branches: " . implode(', ', $userBranchIds) . "\n";
echo "Bilal is super admin: " . ($bilal->isSuperAdmin() ? 'yes' : 'no') . "\n\n";

// Get all products with manufacturing metadata
$products = Product::query()
    ->where('status', 'active')
    ->whereNotNull('metadata')
    ->get();

echo "=== Products with manufacturing_added_at today ===\n";
$totalQtyFromNewMfr = 0;
$productsWithNewMfr = [];

foreach ($products as $product) {
    $meta = $product->metadata ?? [];
    
    if (!isset($meta['manufacturing_added_at'])) {
        continue;
    }
    
    try {
        $addedDate = Carbon::parse($meta['manufacturing_added_at'])->toDateString();
        if ($addedDate !== $today) {
            continue;
        }
    } catch (\Exception $e) {
        continue;
    }
    
    // Check new_mfr_quantity
    $newMfrQty = (float)($meta['new_mfr_quantity'] ?? 0);
    if ($newMfrQty <= 0) {
        continue;
    }
    
    $totalQtyFromNewMfr += $newMfrQty;
    $productsWithNewMfr[] = [
        'id' => $product->id,
        'name' => $product->name,
        'new_mfr_quantity' => $newMfrQty,
        'manufacturing_by_branch' => $meta['manufacturing_by_branch'] ?? [],
        'manufacturing_daily_qty_by_branch' => $meta['manufacturing_daily_qty_by_branch'] ?? [],
    ];
    
    echo "\nProduct #{$product->id}: {$product->name}\n";
    echo "  new_mfr_quantity: $newMfrQty\n";
    echo "  manufacturing_by_branch: " . json_encode($meta['manufacturing_by_branch'] ?? []) . "\n";
    echo "  manufacturing_daily_qty_by_branch: " . json_encode($meta['manufacturing_daily_qty_by_branch'] ?? []) . "\n";
}

echo "\n\n=== Filter Check: manufacturing_by_branch with qty > 0 ===\n";
$productsPassingManufacturingByBranchFilter = [];

foreach ($productsWithNewMfr as $item) {
    $manufacturingByBranch = $item['manufacturing_by_branch'];
    if (empty($manufacturingByBranch)) {
        echo "Product #{$item['id']}: {$item['name']} - FAILED: empty manufacturing_by_branch\n";
        continue;
    }
    
    // Check if ANY of user's branches have manufactured this product
    $hasUserBranchData = false;
    foreach ($userBranchIds as $branchId) {
        if (isset($manufacturingByBranch[$branchId]) && (float)$manufacturingByBranch[$branchId] > 0) {
            $hasUserBranchData = true;
            echo "Product #{$item['id']}: {$item['name']} - PASSED: branch $branchId has qty\n";
            break;
        }
    }
    
    if (!$hasUserBranchData) {
        echo "Product #{$item['id']}: {$item['name']} - FAILED: no user branch in manufacturing_by_branch\n";
    } else {
        $productsPassingManufacturingByBranchFilter[] = $item;
    }
}

echo "\n\n=== Sum Check ===\n";
echo "Products with new_mfr_quantity > 0 today: " . count($productsWithNewMfr) . "\n";
echo "Sum of new_mfr_quantity: $totalQtyFromNewMfr\n";
echo "Products passing manufacturing_by_branch filter: " . count($productsPassingManufacturingByBranchFilter) . "\n";
echo "Sum of filtered products: " . array_sum(array_column($productsPassingManufacturingByBranchFilter, 'new_mfr_quantity')) . "\n";

echo "\n\n=== Phantom Units Analysis ===\n";
if (count($productsWithNewMfr) > count($productsPassingManufacturingByBranchFilter)) {
    echo "FOUND PHANTOM UNITS:\n";
    foreach ($productsWithNewMfr as $item) {
        if (!in_array($item, $productsPassingManufacturingByBranchFilter, true)) {
            echo "  Product #{$item['id']}: {$item['name']} ({$item['new_mfr_quantity']} units)\n";
            echo "    manufacturing_by_branch: " . json_encode($item['manufacturing_by_branch']) . "\n";
        }
    }
} else {
    echo "No phantom units found - filter and sum are in sync!\n";
}
