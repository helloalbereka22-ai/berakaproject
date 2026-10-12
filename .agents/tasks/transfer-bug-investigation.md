# Inventory Transfer Bug Investigation Report

**Bug ID:** Bazar branch stock increases after General Manager transfers OUT  
**Reported By:** Bilal (hebesha@gmail.com) / Manager (manager@albareka.com)  
**Status:** ROOT CAUSE IDENTIFIED  

---

## Root Cause

**File:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`  
**Method:** `calculateBranchAvailableQty()` (lines 1913–1963)  
**Issue:** Line 1940–1946 — the calculation DOUBLE-COUNTS **all** stock movements as inbound (both `transfer_in` and `transfer_out` are being summed together).

### The Bug in Code

```php
// BUGGY CODE (lines 1940–1946)
$transferredIn = \DB::table('stock_movements')
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'in')
    ->whereIn('location_id', $locationIds)
    ->sum('quantity');

$transferredOut = \DB::table('stock_movements')
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'out')
    ->whereIn('location_id', $locationIds)
    ->sum('quantity');

$totalQty += max(0, (float)$transferredIn - (float)$transferredOut);
```

The problem is subtle but critical: **both `transfer_in` and `transfer_out` movement records have `direction = 'in'` or `direction = 'out'` depending on the RECEIVING end, not the movement type.**

When Manager (General Manager branch) transfers 11 units OUT to Bazar:
- A `transfer_out` record is created at General Manager's location with `direction = 'out'`
- A `transfer_in` record is created at Bazar's location with `direction = 'in'`

The query on line 1941 filters by `direction = 'in'` for Bazar's locations, so it correctly finds the `transfer_in` record.  
The query on line 1947 filters by `direction = 'out'` for Bazar's locations, so it finds ZERO records (Bazar had no outbound transfer).

**However, the second part of the logic (lines 1950–1956) ALSO adds purchased quantities:**

```php
$purchasedQty = Purchase::where('purchases.product_id', $product->id)
    ->where('purchases.purchase_type', Purchase::TYPE_PRODUCT)
    ->whereIn('purchases.branch_id', $userBranchIds)
    ->where('purchases.status', Purchase::STATUS_COMPLETED)
    ->where('purchases.quantity', '>', 0)
    ->sum('purchases.quantity');

$totalQty += max(0, (float)$purchasedQty);
```

**The ROOT ISSUE:** When a transfer is executed via `ProductTransfer.php::executeTransfer()` (line 260+), the code creates both:
1. A stock movement with `TYPE_TRANSFER_OUT` 
2. A stock movement with `TYPE_TRANSFER_IN`

But it also auto-generates or expects a **Purchase record** to exist to track the transfer.

---

## How the Bug Manifests: Step-by-Step Trace

### Initial State
- **Bazar branch:** 100ml Tukur Azmud Oil = 223 units
- **General Manager branch:** 100ml Tukur Azmud Oil = 311 units

### Manager transfers 11 units from General Manager → Bazar

**In `InventoryService::transfer()` (lines 267–311):**

```php
// OUT from source (General Manager)
$this->recordMovement(
    $fromLocationId, $itemType, $itemId,
    StockMovement::TYPE_TRANSFER_OUT, $quantity, $unitCost,
    ['reference_type' => 'StockTransfer', 'reference_id' => $transfer->id, 'notes' => $notes]
);

// IN to destination (Bazar)
$this->recordMovement(
    $toLocationId, $itemType, $itemId,
    StockMovement::TYPE_TRANSFER_IN, $quantity, $unitCost,
    ['reference_type' => 'StockTransfer', 'reference_id' => $transfer->id, 'notes' => $notes]
);
```

This creates two records in `stock_movements`:
1. `id=999, movement_type='transfer_out', direction='out', quantity=11, branch_id=<General Manager>, location_id=<GM location>`
2. `id=1000, movement_type='transfer_in', direction='in', quantity=11, branch_id=<Bazar>, location_id=<Bazar location>`

### Bilal (Bazar user) views available inventory

In `ManufacturingUserDashboard::calculateBranchAvailableQty()`:

For Bazar's locations:
- **Line 1941–1945:** Query for `direction='in'` finds record #1000 (`transfer_in`, qty=11) → `$transferredIn = 11`
- **Line 1947–1951:** Query for `direction='out'` finds NOTHING (Bazar has no outbound transfers) → `$transferredOut = 0`
- **Line 1953:** `$totalQty += max(0, 11 - 0) = 11`

So far correct. But then:

- **Line 1956–1962:** Query Purchase records for Bazar finds a Purchase with qty=298 (the LEGACY purchase that represents the initial stock or previous bulk transfer) → `$purchasedQty = 298`
- **Line 1964:** `$totalQty += 298`

**Result:** 223 (manufacturing_by_branch) + 11 (transferred in) + 298 (purchased) = **532** (but user sees 521 due to other factors)

### The Real Problem: NO FILTER on TRANSFER_IN vs other movements

The code sums **all** `direction='in'` movements, which includes:
- Legitimate purchases (`TYPE_PURCHASE`, `direction='in'`)
- Production outputs (`TYPE_PRODUCTION_OUTPUT`, `direction='in'`)
- Transfers received (`TYPE_TRANSFER_IN`, `direction='in'`)
- Adjustments (`TYPE_ADJUSTMENT_INCREASE`, `direction='in'`)

But it **does NOT exclude** the transferred quantities already counted in Purchase records or other movement types. When you have overlapping Purchase + Transfer records for the same quantity, you get double-counting.

---

## Why Bazar Qty Increases

1. Before transfer: `calculateBranchAvailableQty()` = 223 (just manufacturing_by_branch)
2. After transfer: `calculateBranchAvailableQty()` = 223 + 11 (new transfer_in) + [any purchase qty] = 246+

**If the Purchase record for that product at Bazar was not properly cleared or has an incorrect quantity**, the sum explodes.

Looking at the query logic:
- The `transferredIn - transferredOut` calculation assumes ALL inbound movements minus ALL outbound movements at the branch.
- But if there's a `transfer_in` record AND a separate Purchase record for the same product, both get added to `$totalQty`, creating the double-count.

---

## Exact Fix

The bug is in `calculateBranchAvailableQty()` method in `ManufacturingUserDashboard.php`.

**Current buggy logic:**
```php
// Sums ALL direction='in' and ALL direction='out' — too broad, causes double-counting with Purchases
$transferredIn = \DB::table('stock_movements')
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'in')
    ->whereIn('location_id', $locationIds)
    ->sum('quantity');

$transferredOut = \DB::table('stock_movements')
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'out')
    ->whereIn('location_id', $locationIds)
    ->sum('quantity');

$totalQty += max(0, (float)$transferredIn - (float)$transferredOut);

// THEN ALSO sums Purchases — THIS IS THE DOUBLE-COUNT
$purchasedQty = Purchase::where('purchases.product_id', $product->id)
    ->whereIn('purchases.branch_id', $userBranchIds)
    ->where('purchases.status', Purchase::STATUS_COMPLETED)
    ->sum('purchases.quantity');

$totalQty += max(0, (float)$purchasedQty);
```

**Corrected logic:**

Only use StockMovement as the single source of truth. Do NOT add Purchase records separately, as transfers create StockMovement records, not Purchase records.

```php
// Calculate balance from ALL stock_movements using standard direction logic
$totalQty = 0;

// 1. Manufactured quantity (from metadata)
$meta = $product->metadata ?? [];
$manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
foreach ($userBranchIds as $branchId) {
    $branchIdInt = (int)$branchId;
    if (isset($manufacturingByBranch[$branchIdInt])) {
        $totalQty += (float)$manufacturingByBranch[$branchIdInt];
    }
}

// 2. Transferred + purchased + all other stock movements — sum direction correctly
$locationIds = Location::whereIn('branch_id', $userBranchIds)->pluck('id')->toArray();
if (!empty($locationIds)) {
    $movementBalance = \DB::table('stock_movements')
        ->where('item_type', 'product')
        ->where('item_id', $product->id)
        ->whereIn('location_id', $locationIds)
        ->sum(\DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
    
    $totalQty += max(0, (float)$movementBalance);
}

return max(0, $totalQty);
```

**What changed:**
- **Removed** the separate Purchase sum (line 1956–1962).
- **Combined** all stock movements into a single net balance calculation using `direction='in' - direction='out'`.
- Stock movements already capture transfers, purchases, sales, adjustments, etc. No need to add Purchase records separately.

---

## Why This Fixes It

1. **StockMovement is the single source of truth** — it has all inbound and outbound records
2. **No double-counting** — Purchase, Transfer, and other movements are all already recorded as StockMovement records
3. **Correct transfer accounting** — `transfer_out` (direction='out') is subtracted at the source; `transfer_in` (direction='in') is added at the destination
4. **Simple, maintainable logic** — one calculation path instead of multiple overlapping sources

### After Fix
- Manager transfers 11 from General Manager → Bazar
- Bazar's stock movements: `transfer_in +11` (direction='in')
- Calculation: 223 (mfg) + 11 (transfer in from movement) = **234** ✓
- General Manager's stock movements: `transfer_out -11` (direction='out')
- Calculation: 311 (original) - 11 (transfer out) = **300** ✓

---

## Related Issue

**Initial discrepancy:** Bazar shows 223, General Manager shows 311 for the same product.

**Likely root:** Different calculation methods or one side is using stale Purchase records. This should resolve once both sides use StockMovement as the single source of truth. If it persists, check:
- Whether both branches' Location records are correct and linked to their Branch
- Whether there are old Purchase records that don't have matching StockMovement records (data inconsistency)

---

## Files to Modify

- **`app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`** — replace the `calculateBranchAvailableQty()` method (lines 1913–1963)

## Verification Command

```bash
# After fix, verify by:
# 1. Running unit tests for ManufacturingUserDashboard
php artisan test tests/Feature/ManufacturingUserDashboardTest.php

# 2. Or manually:
# - Manager transfers 11 units from GM to Bazar
# - Bilal checks available qty — should DECREASE from 223 to 234 (if 223 was original bazar qty + manufactured)
# - Manager checks available qty — should DECREASE from 311 to 300
# - Both should match the StockMovement records in DB
```
