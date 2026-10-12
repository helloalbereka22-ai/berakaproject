<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Product;
use App\Models\User;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

// Find product ID for "100 ml Tukur Azmud Oil"
$product = Product::where('name', 'like', '%Tukur Azmud%')
    ->where('name', 'like', '%100%')
    ->first();

if (!$product) {
    echo "Product not found\n";
    exit(1);
}

echo "=== PRODUCT ===\n";
echo "ID: {$product->id}\n";
echo "Name: {$product->name}\n\n";

// Get Bilal user
$bilal = User::where('email', 'hebesha@gmail.com')->first();
echo "=== BILAL USER ===\n";
echo "ID: {$bilal->id}\n";
echo "Name: {$bilal->name}\n";
echo "Branch IDs: " . json_encode($bilal->assignedBranchIds()) . "\n\n";

// Get all stock movements for this product grouped by type and direction
echo "=== STOCK MOVEMENTS BY TYPE & DIRECTION ===\n";
$rows = DB::table('stock_movements')
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->selectRaw('movement_type, direction, SUM(quantity) as total, COUNT(*) as count')
    ->groupBy('movement_type', 'direction')
    ->orderBy('direction')
    ->orderBy('movement_type')
    ->get();

foreach ($rows as $row) {
    echo "Type: {$row->movement_type}, Direction: {$row->direction}, Total: {$row->total}, Count: {$row->count}\n";
}
echo "\n";

// Check product metadata
echo "=== PRODUCT METADATA ===\n";
$meta = is_array($product->metadata) ? $product->metadata : json_decode($product->metadata, true);
echo json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Calculate using ProductTransfer's getSourceBalance method
echo "=== PRODUCT TRANSFER CALCULATION (getSourceBalance logic) ===\n";
$locationIds = DB::table('locations')
    ->whereIn('branch_id', $bilal->assignedBranchIds())
    ->pluck('id');

$stockIn = (float) StockMovement::whereIn('location_id', $locationIds)
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'in')
    ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
    ->sum('quantity');

$stockOut = (float) StockMovement::whereIn('location_id', $locationIds)
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'out')
    ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
    ->sum('quantity');

$movementBalance = max(0, (float)$stockIn - (float)$stockOut);
echo "Stock In (excluding " . json_encode(StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND) . "): $stockIn\n";
echo "Stock Out (only " . json_encode(StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND) . "): $stockOut\n";
echo "Movement Balance: $movementBalance\n";
echo "ProductTransfer Result: " . max(0, $movementBalance) . "\n\n";

// Calculate using ManufacturingUserDashboard's calculateBranchAvailableQty
echo "=== MANUFACTURING DASHBOARD CALCULATION (calculateBranchAvailableQty) ===\n";

// 1. Manufactured quantity from metadata
$manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
$metadataQty = 0;
foreach ($bilal->assignedBranchIds() as $branchId) {
    $branchIdInt = (int)$branchId;
    if (isset($manufacturingByBranch[$branchIdInt])) {
        $metadataQty += (float)$manufacturingByBranch[$branchIdInt];
    }
}
echo "Manufactured Qty (from metadata): $metadataQty\n";

// 2. Stock movement balance (same as ProductTransfer)
echo "Stock Movement Balance: $movementBalance\n";

$totalQty = $metadataQty + $movementBalance;
echo "TOTAL (metadata + movements): $totalQty\n\n";

echo "=== SUMMARY ===\n";
echo "ProductTransfer shows: $movementBalance\n";
echo "ManufacturingUserDashboard shows: $totalQty\n";
echo "Difference: " . ($totalQty - $movementBalance) . " (overcounted by metadata: $metadataQty)\n";
?>
