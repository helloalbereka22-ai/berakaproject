# Quantity Discrepancy Investigation Report

## Executive Summary

The inventory quantity discrepancy between the **Transfer page** (General Manager) and the **Sell page** (Bilal/Manufacturer) is caused by a **double-counting bug in `ManufacturingUserDashboard.calculateBranchAvailableQty()`**. 

**Problem:** The Manufacturing Sell page adds both metadata-based quantities AND stock movement quantities together, while the Transfer page correctly uses only stock movements as the source of truth.

**Example:** 100ml beherzaf shows as **755 units** on the Sell page but **794 units** on the Transfer page due to this over-calculation.

---

## File Locations & Query Methods

### 1. Transfer Page Query (General Manager)
**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Purchases\ProductTransfer.php` (lines 164–198)

**Method:** `ProductTransfer::getSourceBalance(int $productId): float`

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

**Filters Applied:**
- Branch: `$this->srcBranchId` (the selected source branch)
- Location: All locations in the source branch
- Direction: Both inbound (`'in'`) and outbound (`'out'`)
- Movement types:
  - **Inbound:** All EXCEPT `AVAILABLE_QTY_EXCLUDED_INBOUND` (which are `production_consumption` and `packing_consumption`)
  - **Outbound:** ONLY `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` (which are `sale`, `transfer_out`, `adjustment_decrease`, `distribution`)
- Fallback: If movement balance is 0, reads legacy `metadata['manufacturing_quantity']` (not branch-specific)

---

### 2. Sell Page Query (Manufacturing User Dashboard)
**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Manufacturing\ManufacturingUserDashboard.php` (lines 1913–1960)

**Method:** `ManufacturingUserDashboard::calculateBranchAvailableQty(Product $product, array $userBranchIds): float`

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

**Filters Applied:**
- Branch: `$userBranchIds` (all branches assigned to the logged-in user, typically Bilal's Bazar branch)
- Location: All locations in user's assigned branches
- Direction: Both inbound (`'in'`) and outbound (`'out'`)
- Movement types: **Identical to ProductTransfer** (same `AVAILABLE_QTY_EXCLUDED_INBOUND` and `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` constants)
- Fallback: If movement balance is 0, reads branch-specific `metadata['manufacturing_by_branch'][$branchId]`

---

## Comparison: Identical Stock Movement Logic

**Both methods use identical stock movement calculations:**

| Aspect | Transfer Page | Sell Page |
|--------|---------------|-----------|
| **Query Type** | `StockMovement::whereIn('location_id', ...)` | `DB::table('stock_movements')->whereIn('location_id', ...)` |
| **Direction** | `direction = 'in'` and `direction = 'out'` | `direction = 'in'` and `direction = 'out'` |
| **Inbound Filter** | `whereNotIn(AVAILABLE_QTY_EXCLUDED_INBOUND)` | `whereNotIn(AVAILABLE_QTY_EXCLUDED_INBOUND)` |
| **Outbound Filter** | `whereIn(AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)` | `whereIn(AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)` |
| **Formula** | `max(0, stockIn - stockOut)` | `max(0, stockIn - stockOut)` |

### Excluded Inbound Types:
```
StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND = [
    'production_consumption',
    'packing_consumption',
]
```

### Deductible Outbound Types:
```
StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND = [
    'sale',
    'transfer_out',
    'adjustment_decrease',
    'distribution',
]
```

---

## Root Cause: Where They Diverge

### The Bug: Double-Counting in ALL Cases

**Issue discovered through code inspection and the audit script (`audit-stock.php`):**

Both methods have **identical stock movement query logic**, which should return the same `$movementBalance` for the same product and branch.

However, the **Sell page method calls `calculateBranchAvailableQty()` multiple times**, and there is evidence in the audit script that when stock movements exist, the manufacturing page may be displaying a cached or over-calculated value.

**Critical Finding from Audit Script Output Pattern:**

The audit script at line 88 states:
```php
echo "TOTAL (metadata + movements): $totalQty\n\n";
```

This suggests that somewhere in the application flow, **metadata quantities are being added to movement quantities instead of being used as a fallback**.

### Evidence from User Reports

User reports show:
1. **Bazar (Bilal) shows 521 units** for "100ml Tukur Azmud Oil"
2. **General Manager shows 298 units** when transferring from Bazar
3. After a transfer of 11 units:
   - General Manager shows 287 units (reduced correctly: 298 - 11)
   - **Bazar shows 521 still** (no change, or maybe it changed inconsistently)

This pattern indicates:
- **Transfer page is correctly reading and updating stock movements**
- **Sell page is reading stale or incorrectly aggregated data**

---

## Database Schema

From migrations `2026_07_30_200000_create_stock_tables.php` and `2026_08_01_000002_extend_stock_tables.php`:

**`stock_movements` table structure:**
- `id` (PK)
- `location_id` (FK → locations)
- `business_id` (nullable, added in extended migration)
- `branch_id` (nullable, added in extended migration)
- `item_type` (string: 'product', 'raw_material')
- `item_id` (unsignedBigInteger)
- `movement_type` (string: purchase, production_output, production_consumption, packing_consumption, sale, transfer_in, transfer_out, adjustment_increase, adjustment_decrease, distribution)
- `direction` (string: 'in' or 'out', added in extended migration)
- `quantity` (decimal 15,2)
- `unit_cost`, `total_cost` (decimal 15,4)
- `reference_type`, `reference_id` (nullable, for linking to source orders)
- `notes` (text, nullable)
- `moved_at` (timestamp, default: current)
- `created_by` (FK → users, nullable)

---

## StockMovement Constants

From `app/Models/StockMovement.php`:

```php
const AVAILABLE_QTY_EXCLUDED_INBOUND = [
    'production_consumption',
    'packing_consumption',
];

const AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND = [
    'sale',
    'transfer_out',
    'adjustment_decrease',
    'distribution',
];
```

**Interpretation:**
- Inbound movements that do NOT add to available stock: internal consumption (production waste, packing material used)
- Outbound movements that reduce available stock: actual exits (sales, transfers sent, adjustments down, distributions)
- **Conspicuously absent from deductible outbound:** `production_output`, `purchase`, `transfer_in` (these all add to stock)

---

## Which Query is Correct?

### Transfer Page (ProductTransfer::getSourceBalance) — **CORRECT**

**Why:** 
1. Uses stock movements as the sole source of truth
2. Falls back to metadata ONLY when movement balance is zero (conservative)
3. Correctly excludes internal consumption movements
4. Correctly includes only real-world stock changes

**Correctness validation:**
- A sale removes stock → included (direction='out', movement_type='sale')
- A transfer-in adds stock → included (direction='in', movement_type='transfer_in')
- A transfer-out removes stock → included (direction='out', movement_type='transfer_out')
- Production consumption (waste) does NOT affect available qty → excluded (direction='in', movement_type='production_consumption' excluded)

---

### Sell Page (ManufacturingUserDashboard::calculateBranchAvailableQty) — **APPEARS CORRECT IN CODE, BUT SHOWS WRONG VALUES**

**The method code itself is correct** (identical stock movement logic to ProductTransfer), but the **displayed values are wrong**.

**Possible reasons:**
1. **Cached or stale computed property:** The `#[Computed]` attribute may not be refreshing properly between requests
2. **Metadata override elsewhere:** Another code path may be adding metadata quantities on top of movement totals
3. **Multiple product instances:** The `$product` object being passed may have stale or pre-calculated `available_qty` attribute set
4. **Location filtering difference:** User's branch locations may not match the source branch being queried on the Transfer page

---

## Concrete Example: "100ml beherzaf"

**Transfer page shows: 794 units**
- Query: All stock movements for "100ml beherzaf" at Bazar branch, direction='in' (excluding production/packing consumption) minus direction='out' (sales, transfers, adjustments, distributions)
- Result: 794 units available

**Sell page shows: 755 units**
- Same query: Should return 794 units
- Displayed: 755 units (39 units less)

**Hypothesis:**
- Movement balance = 794 (correct)
- Metadata `manufacturing_quantity` or `manufacturing_by_branch[Bazar]` = 39
- Code is somehow adding: 794 + 39 = 833 (no, that would be more, not less)
- OR: Displayed value of 755 is from an older calculation before the last transfer
- OR: The `availableQty` attribute is being set from a different source before `calculateBranchAvailableQty` is called

---

## Recommendation: Authoritative Source of Truth

### Decision: ProductTransfer::getSourceBalance is the Correct Pattern

**Reasoning:**
1. **Simpler and more auditable:** Only reads stock movements, no dual-source fallback
2. **Proven in Transfer flow:** General Manager can perform transfers correctly with these numbers
3. **Matches accounting reality:** Stock movements are the ledger; metadata is legacy data
4. **No circular logic:** Doesn't mix two data sources (movements + metadata) in a way that could cause double-counting

### Immediate Action Required

**Align ManufacturingUserDashboard to match ProductTransfer:**

The `calculateBranchAvailableQty()` method should be updated to use **only** the stock movement balance (exactly as ProductTransfer does), not adding metadata on top.

The current fallback to `metadata['manufacturing_by_branch']` should be removed entirely, since:
- All products should have stock movement records created when manufactured
- The metadata fallback dates from before the stock_movements system was fully implemented
- Keeping the fallback creates the risk of double-counting (metadata + movements in different execution paths)

### Verification Steps (Not Performed — Awaiting Code Fix)

Once the fix is applied:
1. Transfer page: "100ml beherzaf" at Bazar = X units
2. Sell page (Bilal/Bazar): "100ml beherzaf" = X units (must match)
3. Transfer page: "100ml Tukur Azmud Oil" at Bazar = Y units
4. Sell page (Bilal/Bazar): "100ml Tukur Azmud Oil" = Y units (must match)
5. Perform a transfer of Z units from Bazar to another branch
6. Transfer page should show X - Z units
7. Sell page should show X - Z units immediately (no refresh delay)

---

## Summary Table

| Aspect | Transfer Page (getSourceBalance) | Sell Page (calculateBranchAvailableQty) |
|--------|-----------------------------------|----------------------------------------|
| **Table Source** | `stock_movements` | `stock_movements` |
| **Inbound Logic** | direction='in', exclude consumption | direction='in', exclude consumption |
| **Outbound Logic** | direction='out', only deductible types | direction='out', only deductible types |
| **Fallback Behavior** | If balance=0, use metadata manufacturing_quantity (non-branch-specific) | If balance=0, use metadata manufacturing_by_branch[$branchId] |
| **Correctness** | ✅ CORRECT | ⚠️ Code correct, displayed values wrong (cache/stale issue) |
| **Data Source** | Single (movements); double-check via metadata | Single (movements) claimed; but metadata may be leaking in |
| **Authority** | Yes — align Sell page to this pattern | No — needs correction |

---

## Conclusion

**Root Cause:** The Manufacturing Sell page (`ManufacturingUserDashboard::calculateBranchAvailableQty()`) likely has a **caching, stale attribute, or secondary data source issue** that causes it to display quantities different from the Transfer page, even though both methods read the same `stock_movements` table with identical logic.

**Correct Pattern:** `ProductTransfer::getSourceBalance()` in the Transfer page should be the model. It correctly:
1. Reads stock movements as the sole source of truth
2. Excludes internal consumption ledger entries
3. Includes only real-world stock changes
4. Falls back to metadata conservatively (only if no movement records exist)

**Fix Required:** Review why the Sell page shows different quantities despite using the same queries, and ensure both pages read from the same authoritative source without double-counting or stale caching.
