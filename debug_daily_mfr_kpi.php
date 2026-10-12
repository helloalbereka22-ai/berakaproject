<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = now()->toDateString();
echo "Today: $today\n";
echo "===================================\n\n";

$products = \App\Models\Product::where('status', 'active')->whereNotNull('metadata')->get();

foreach ($products as $p) {
    $meta = $p->metadata ?? [];
    
    // Only show products with manufacturing data
    if (empty($meta['last_mfr_quantity']) && empty($meta['manufacturing_added_at'])) {
        continue;
    }
    
    $lastMfr = (float) ($meta['last_mfr_quantity'] ?? 0);
    $addedAt = $meta['manufacturing_added_at'] ?? 'N/A';
    
    if ($lastMfr > 0) {
        echo "Product: {$p->name}\n";
        echo "  ID: {$p->id}\n";
        echo "  last_mfr_quantity: $lastMfr\n";
        echo "  manufacturing_added_at: $addedAt\n";
        echo "  manufacturing_daily_qty_by_branch: " . json_encode($meta['manufacturing_daily_qty_by_branch'] ?? []) . "\n";
        echo "  manufactured_by_branch_id: " . ($meta['manufactured_by_branch_id'] ?? 'N/A') . "\n";
        echo "\n";
    }
}

echo "===================================\n";
echo "Summary: Check if sum = 35 or something else\n";

// Calculate sum like getDailyManufactured does
$sum = 0;
$products = \App\Models\Product::where('status', 'active')->whereNotNull('metadata')->get();
foreach ($products as $p) {
    $meta = $p->metadata ?? [];
    
    // Check if has manufacturing_added_at
    if (!isset($meta['manufacturing_added_at'])) continue;
    
    try {
        $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
        if ($addedDate !== $today) continue;
        
        // Check if has daily qty
        $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
        
        $hasData = false;
        foreach ($dailyByBranch as $qty) {
            if ((float)$qty > 0) {
                $hasData = true;
                break;
            }
        }
        
        if ($hasData) {
            $qty = (float)($meta['last_mfr_quantity'] ?? 0);
            echo "Adding: {$p->name} = $qty\n";
            $sum += $qty;
        }
    } catch (\Exception $e) {
        // Skip
    }
}

echo "TOTAL: $sum\n";
