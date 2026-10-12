# Investigation Report: Bilal Stock Quantity Discrepancy (521 vs 298)

**Date:** 2025  
**Product:** 100 ml Tukur Azmud Oil  
**Issue:** Bilal sees 521.000 in Sell Products, General Manager sees 298 in Transfer Products  
**Expected Value:** 298  
**Status:** READ-ONLY INVESTIGATION COMPLETE

---

## 1. Root Cause: Dual-Counting in calculateBranchAvailableQty

### The Problem
The ManufacturingUserDashboard's `calculateBranchAvailableQty()` method **adds both metadata quantities AND stock movements**. Meanwhile, the ProductTransfer's `getSourceBalance()` method uses **only stock movements** as the single source of truth.

This causes 521 = 223 (old metadata) + 298 (stock movements) on Bilal's Sell Products page, while Transfer Products correctly shows 298.

### File Locations
- **Bilal's Sell Products (overcounting):** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`
  - Method: `calculateBranchAvailableQty()` (lines 1913-1957)
  - Computed properties: `allSellProducts()` (lines 1809-1831), `filteredSellProducts()` (lines 1833-1863)
  
- **General Manager's Transfer Products (correct):** `app/Livewire/Admin/Purchases/ProductTransfer.php`
  - Method: `getSourceBalance()` (lines 161-192)

---

## 2. Code Comparison: The Filter Difference

### ManufacturingUserDashboard::calculateBranchAvailableQty (WRONG - ADDS METADATA)

```php
private function calculateBranchAvailableQty(Product $product, array $userBranchIds): float
{
    if (empty($userBranchIds)) {
        return 0;
    }

    $totalQty = 0;

    // 1. Get manufactured quantity (from metadata) ← ⚠️ DOUBLES STOCK
    $meta = $product->metadata ?? [];
    $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
    foreach ($userBranchIds as $branchId) {
        $branchIdInt = (int)$branchId;
        if (isset($manufacturingByBranch[$branchIdInt])) {
            $totalQty += (float)$manufacturingByBranch[$branchIdInt];  // ← ADDS METADATA
        }
    }

    // 2. Get stock movements as single source of truth...
    $locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
    if (!empty($locationIds)) {
        // Inbound movements (excluding production/packing consumption)
        $stockIn = (float) \DB::table('stock_movements')
            ->whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $product->id)
            ->where('direction', 'in')
            ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
            ->sum('quantity');

        // Outbound movements (only sales, transfers, adjustments, distributions - exclude consumption)
        $stockOut = (float) \DB::table('stock_movements')
            ->whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $product->id)
            ->where('direction', 'out')
            ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
            ->sum('quantity');

        $movementBalance = max(0, $stockIn - $stockOut);
        $totalQty += (float)$movementBalance;  // ← ADDS STOCK MOVEMENTS (NOW SUM OF BOTH)
    }

    return max(0, $totalQty);
}
```

**Result:** Returns `$metadataQty + $movementBalance` = 223 + 298 = **521 ✗ WRONG**

---

### ProductTransfer::getSourceBalance (CORRECT - STOCK MOVEMENTS ONLY)

```php
private function getSourceBalance(int $productId): float
{
    if (!$this->srcBranchId) return 0;

    $locationIds = Location::where('branch_id', $this->srcBranchId)->pluck('id');
    if ($locationIds->isEmpty()) {
        return 0;
    }

    $stockIn = (float) StockMovement::whereIn('location_id', $locationIds)
        ->where('item_type', 'product')
        ->where('item_id', $productId)
        ->where('direction', 'in')
        ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
        ->sum('quantity');

    $stockOut = (float) StockMovement::whereIn('location_id', $locationIds)
        ->where('item_type', 'product')
        ->where('item_id', $productId)
        ->where('direction', 'out')
        ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
        ->sum('quantity');

    $movementBalance = max(0, (float)$stockIn - (float)$stockOut);

    // If no StockMovement records, check for legacy metadata
    if ($movementBalance == 0) {
        $product = Product::withoutGlobalScopes()->find($productId);
        if ($product) {
            $metadata = $product->metadata ?? [];
            $mfgQty = (float) ($metadata['manufacturing_quantity'] ?? 0);
            return max(0, $mfgQty);
        }
    }

    return $movementBalance;
}
```

**Result:** Returns `$movementBalance` only = **298 ✓ CORRECT**  
(Falls back to metadata only if NO stock movements exist, not additive)

---

## 3. StockMovement Filter Constants (From app/Models/StockMovement.php)

```php
const AVAILABLE_QTY_EXCLUDED_INBOUND = [
    self::TYPE_PRODUCTION_CONSUMPTION,
    self::TYPE_PACKING_CONSUMPTION,
];

const AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND = [
    self::TYPE_SALE,
    self::TYPE_TRANSFER_OUT,
    self::TYPE_ADJUSTMENT_DECREASE,
    self::TYPE_DISTRIBUTION,
];
```

**Both ProductTransfer and ManufacturingUserDashboard use the same constants for stock movements**  
→ The difference is that ManufacturingUserDashboard ALSO adds metadata on top.

---

## 4. Where Does the 521 Come From?

**Metadata Value (manufacturing_by_branch):** 223  
- This represents the manufactured quantity recorded in product metadata before the transfer

**Stock Movement Balance:** 298  
- Bilal transfers 11 from 223 → leaves 212 at source
- But on General Manager side, he receives 11, so destination now shows 298
  (This 298 is the accumulated stock movements after transfer, not metadata)

**Formula:** `521 = 223 (metadata) + 298 (stock movements)`

---

## 5. Why This Happens: The Double-Entry Problem

1. When Bilal manufactures a product → `manufacturing_quantity` stored in metadata
2. When transfer happens → StockMovement records created with TYPE_PRODUCTION_OUTPUT or TYPE_TRANSFER_IN
3. **ManufacturingUserDashboard adds BOTH** → overcounts by the old metadata value
4. **ProductTransfer uses ONLY stock movements** (or metadata only as fallback) → correct

---

## 6. Code Change Required

**File:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`  
**Method:** `calculateBranchAvailableQty()` (lines 1913-1957)

### Before (WRONG - adds metadata AND movements):
```php
private function calculateBranchAvailableQty(Product $product, array $userBranchIds): float
{
    if (empty($userBranchIds)) {
        return 0;
    }

    $totalQty = 0;

    // 1. Get manufactured quantity (from metadata)
    $meta = $product->metadata ?? [];
    $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
    foreach ($userBranchIds as $branchId) {
        $branchIdInt = (int)$branchId;
        if (isset($manufacturingByBranch[$branchIdInt])) {
            $totalQty += (float)$manufacturingByBranch[$branchIdInt];
        }
    }

    // 2. Get stock movements as single source of truth, using same filters as ProductTransfer
    $locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
    if (!empty($locationIds)) {
        // Inbound movements (excluding production/packing consumption which are internal ledger entries)
        $stockIn = (float) \DB::table('stock_movements')
            ->whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $product->id)
            ->where('direction', 'in')
            ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
            ->sum('quantity');

        // Outbound movements (only sales, transfers, adjustments, distributions - exclude consumption)
        $stockOut = (float) \DB::table('stock_movements')
            ->whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $product->id)
            ->where('direction', 'out')
            ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
            ->sum('quantity');

        $movementBalance = max(0, $stockIn - $stockOut);
        $totalQty += (float)$movementBalance;
    }

    return max(0, $totalQty);
}
```

### After (CORRECT - stock movements only, metadata as fallback):
```php
private function calculateBranchAvailableQty(Product $product, array $userBranchIds): float
{
    if (empty($userBranchIds)) {
        return 0;
    }

    // Get stock movements as SINGLE SOURCE OF TRUTH
    $locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
    if (empty($locationIds)) {
        return 0;
    }

    // Inbound movements (excluding production/packing consumption)
    $stockIn = (float) \DB::table('stock_movements')
        ->whereIn('location_id', $locationIds)
        ->where('item_type', 'product')
        ->where('item_id', $product->id)
        ->where('direction', 'in')
        ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
        ->sum('quantity');

    // Outbound movements (only sales, transfers, adjustments, distributions - exclude consumption)
    $stockOut = (float) \DB::table('stock_movements')
        ->whereIn('location_id', $locationIds)
        ->where('item_type', 'product')
        ->where('item_id', $product->id)
        ->where('direction', 'out')
        ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
        ->sum('quantity');

    $movementBalance = max(0, (float)$stockIn - (float)$stockOut);

    // If no StockMovement records exist, fall back to legacy metadata (not additive)
    if ($movementBalance == 0) {
        $meta = $product->metadata ?? [];
        $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
        $totalQty = 0;
        foreach ($userBranchIds as $branchId) {
            $branchIdInt = (int)$branchId;
            if (isset($manufacturingByBranch[$branchIdInt])) {
                $totalQty += (float)$manufacturingByBranch[$branchIdInt];
            }
        }
        return max(0, $totalQty);
    }

    return max(0, $movementBalance);
}
```

**Key Change:** Remove the metadata addition. Use metadata ONLY as fallback when no stock movements exist.

---

## 7. Metadata Corruption Assessment

**Status:** ✓ NOT A PRIMARY FACTOR

The metadata value (223) is correct for what was manufactured. The problem is not stale metadata, but rather that the code logic double-counts:
- Old metadata: 223 (correctly records what was manufactured)
- Stock movements: 298 (correctly reflects current state after transfers)
- Bug: Both added together = 521

The fix is logic, not data cleanup.

---

## 8. Cache Impact

**Status:** ✗ NOT A FACTOR

The methods use `#[Computed]` Livewire attributes which recalculate on every state change. Caches should not affect this.  
Recommendation: `php artisan view:clear && php artisan cache:clear` after fix (precaution only).

---

## Summary

| Aspect | Finding |
|--------|---------|
| **Root Cause** | `calculateBranchAvailableQty()` adds metadata AND stock movements instead of using stock movements as single source |
| **Exact Location** | `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`, lines 1913-1957 |
| **Correct Value** | 298 (from stock movements only) |
| **Overcounted By** | 223 (obsolete metadata being double-added) |
| **Fix Complexity** | Low - remove metadata summation, use stock movements only, with metadata as fallback when zero movements |
| **Metadata Issue** | No - data is correct, logic is wrong |
| **Cache Issue** | No - not relevant to Computed properties |
| **Files to Change** | 1 file, 1 method (~45 line change) |

---

## Next Steps (Not Performed - READ-ONLY Mode)

1. Apply the code change to `calculateBranchAvailableQty()`
2. Run: `php artisan view:clear && php artisan cache:clear`
3. Test: Bilal's Sell Products should now show 298 (matching General Manager's Transfer view)
4. Verify: No side effects on other quantity displays
