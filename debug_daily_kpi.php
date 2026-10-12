<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Carbon;

$today = Carbon::now()->toDateString();

echo "=== Debugging Daily Manufactured KPI ===\n";
echo "Today's date: $today\n\n";

// Get all products with manufacturing metadata and today's date
$allProducts = \App\Models\Product::withoutGlobalScopes()
    ->where('status', 'active')
    ->whereNotNull('metadata')
    ->get();

echo "Products with manufacturing activity today:\n";
$productsWithTodayActivity = [];

foreach ($allProducts as $p) {
    $meta = $p->metadata ?? [];
    
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
    
    $productsWithTodayActivity[] = $p;
    
    echo "\n[ID: {$p->id}] {$p->name}\n";
    echo "  last_mfr_quantity: " . ($meta['last_mfr_quantity'] ?? 'NOT SET') . "\n";
    echo "  manufacturing_daily_qty_by_branch: " . json_encode($meta['manufacturing_daily_qty_by_branch'] ?? []) . "\n";
    echo "  manufactured_by_branch_id: " . ($meta['manufactured_by_branch_id'] ?? 'NOT SET') . "\n";
    echo "  manufacturing_added_at: " . ($meta['manufacturing_added_at'] ?? 'NOT SET') . "\n";
}

echo "\n\n=== Summation ===\n";

// Sum last_mfr_quantity
$sumLastMfr = collect($productsWithTodayActivity)->sum(function($p) {
    return (float)($p->metadata['last_mfr_quantity'] ?? 0);
});
echo "SUM(last_mfr_quantity): $sumLastMfr\n";

// Sum manufacturing_daily_qty_by_branch for Bazar (bilal's branch)
// First, need to find Bazar's branch ID
$bazarBranch = \App\Models\Branch::where('name', 'Bazar')->first();
$bazarId = $bazarBranch ? $bazarBranch->id : 7;
echo "\nBazar branch ID: $bazarId\n";

$sumDailyByBranch = 0;
foreach ($productsWithTodayActivity as $p) {
    $dailyByBranch = $p->metadata['manufacturing_daily_qty_by_branch'] ?? [];
    $sumDailyByBranch += (float)($dailyByBranch[$bazarId] ?? $dailyByBranch[(string)$bazarId] ?? 0);
}
echo "SUM(manufacturing_daily_qty_by_branch[$bazarId]): $sumDailyByBranch\n";

// Count products
echo "\nTotal products with today's activity: " . count($productsWithTodayActivity) . "\n";

// Show bilal's user details
$bilal = \App\Models\User::where('email', 'hebesha@gmail.com')->first();
if ($bilal) {
    echo "\nBilal details:\n";
    echo "  ID: {$bilal->id}\n";
    echo "  Assigned Branches: " . json_encode($bilal->assignedBranchIds()) . "\n";
}

// Now check getDailyManufactured() logic
echo "\n\n=== getDailyManufactured() filtering logic for bilal ===\n";

if ($bilal) {
    $userBranchIds = $bilal->assignedBranchIds();
    echo "User's branch IDs: " . json_encode($userBranchIds) . "\n";
    
    $items = [];
    foreach ($productsWithTodayActivity as $product) {
        $meta = $product->metadata ?? [];
        
        // Check if THIS BRANCH has daily manufacturing data
        $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
        
        $includeItem = false;
        foreach ($userBranchIds as $branchId) {
            $qty = (float)($dailyByBranch[$branchId] ?? $dailyByBranch[(string)$branchId] ?? 0);
            if ($qty > 0) {
                $includeItem = true;
                echo "\nProduct {$product->id} ({$product->name}): Included for branch $branchId (qty=$qty)\n";
                break;
            }
        }
        
        if ($includeItem) {
            // getDailyManufactured uses last_mfr_quantity
            $qty = (float)($meta['last_mfr_quantity'] ?? 0);
            $items[] = ['name' => $product->name, 'qty' => $qty];
        }
    }
    
    $sumInDailyManufactured = collect($items)->sum('qty');
    echo "\n\nSUM of quantities in getDailyManufactured(): $sumInDailyManufactured\n";
    echo "Items included:\n";
    foreach ($items as $item) {
        echo "  - {$item['name']}: {$item['qty']}\n";
    }
}

?>
