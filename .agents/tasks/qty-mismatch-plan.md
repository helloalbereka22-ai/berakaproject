# Implementation Plan: Fix Quantity Mismatch Between Manufacturing and Transfer Pages

## Problem Summary
The **Manufacturing Products** page (`/admin/manufacturing/products`) and **Transfer Products** page (`/admin/purchases/transfer`) show different available quantities for the same product:
- Manufacturing shows: 64 units (using `max($mfrQty, $stockQty)`)
- Transfer shows: 60 units (using `$stockFromMovements + $fgStock + $metaQty`)

**Root Cause:** Double-counting from three incompatible sources:
1. **metadata['manufacturing_quantity']**: Un-logged manufacturing output
2. **StockMovement** (net of in/out): Actual recorded movements
3. **FinishedGood.quantity_available**: Separate tracking of manufactured batches

The correct source of truth is **StockMovement only**, which captures all inventory events including production output, sales, transfers, and adjustments.

---

## Design Decision
**Unified StockMovement-Only Approach:**
Both methods will use ONLY StockMovement records to calculate available quantity:
- **Stock In:** Sum of all `direction='in'` rows EXCLUDING `production_consumption` and `packing_consumption` movement types
  - Includes: opening, purchase, production_output, sale_return, purchase_return, transfer_in, adjustment_increase
- **Stock Out:** Sum of all `direction='out'` rows with specific types: sale, transfer_out, adjustment_decrease, distribution
- **Net Available:** `max(0, stockIn - stockOut)` per location/branch

This removes double-counting because:
- Manufacturing metadata is converted to a real `production_output` movement in `ProductTransfer.executeTransfer()` (line ~320)
- FinishedGood records are legacy; StockMovement is the authoritative ledger
- When an item is sold, transferred, or distributed, a matching `direction='out'` movement is recorded

**Rationale:** StockMovement is event-sourced (immutable append-only), so it naturally prevents double-counting when properly filtered. Metadata and FinishedGood are derived caches that can become stale.

---

## Implementation Steps

### 1. Update `ManufacturingUserDashboard.resolveAvailableQty()` to use StockMovement only
**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Manufacturing\ManufacturingUserDashboard.php` (method at line ~971)

**Changes:**
- Remove the `max($mfrQty, $stockQty)` logic
- Remove dependency on `metadata['manufacturing_quantity']`
- Simplify to: return the net balance from StockMovement (in minus out)
- Keep the existing `$stockIn` and `$stockOut` calculation for 'product' sell type (lines ~1005–1020)
- Replace `return max($mfrQty, $stockQty)` (line ~1021) with `return max(0, (float)$stockIn - (float)$stockOut)`

**Old code (lines 1003–1021):**
```php
// Source 1: manually logged manufacturing output (metadata)
$metadata = $product->metadata ?? [];
$mfrQty   = (float) ($metadata['manufacturing_quantity'] ?? 0);

// Source 2: net StockMovements (covers production_output transfers in)
$query = StockMovement::where('item_id', $product->id)
    ->where('item_type', 'product');

if (!empty($this->filterBusinessId)) {
    $query->where('business_id', (int) $this->filterBusinessId);
}

$stockIn  = (clone $query)->where('direction', 'in')
    ->whereNotIn('movement_type', [
        StockMovement::TYPE_PRODUCTION_CONSUMPTION,
        StockMovement::TYPE_PACKING_CONSUMPTION,
    ])
    ->sum('quantity');

$stockOut = (clone $query)->where('direction', 'out')
    ->whereIn('movement_type', [
        StockMovement::TYPE_SALE,
        StockMovement::TYPE_TRANSFER_OUT,
        StockMovement::TYPE_ADJUSTMENT_DECREASE,
        StockMovement::TYPE_DISTRIBUTION,
    ])
    ->sum('quantity');

$stockQty = max(0, (float)$stockIn - (float)$stockOut);

return max($mfrQty, $stockQty);
```

**New code:**
```php
$query = StockMovement::where('item_id', $product->id)
    ->where('item_type', 'product');

if (!empty($this->filterBusinessId)) {
    $query->where('business_id', (int) $this->filterBusinessId);
}

$stockIn  = (clone $query)->where('direction', 'in')
    ->whereNotIn('movement_type', [
        StockMovement::TYPE_PRODUCTION_CONSUMPTION,
        StockMovement::TYPE_PACKING_CONSUMPTION,
    ])
    ->sum('quantity');

$stockOut = (clone $query)->where('direction', 'out')
    ->whereIn('movement_type', [
        StockMovement::TYPE_SALE,
        StockMovement::TYPE_TRANSFER_OUT,
        StockMovement::TYPE_ADJUSTMENT_DECREASE,
        StockMovement::TYPE_DISTRIBUTION,
    ])
    ->sum('quantity');

return max(0, (float)$stockIn - (float)$stockOut);
```

**Verification:** Run unit tests for ManufacturingUserDashboard if they exist, or manually verify via UI that the displayed quantity for "100 ml Tukur Azmud Oil" is consistent with actual StockMovement totals.

---

### 2. Update `ProductTransfer.getSourceBalance()` to use StockMovement only
**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Purchases\ProductTransfer.php` (method at line ~165)

**Changes:**
- Remove `$fgStock` (FinishedGood-based) calculation
- Remove `$metaQty` (metadata['manufacturing_quantity']) calculation
- Simplify to: return the net balance from StockMovement for the source branch's locations

**Old code (lines 165–188):**
```php
private function getSourceBalance(int $productId): float
{
    if (!$this->srcBranchId) return 0;

    $locationIds = Location::where('branch_id', $this->srcBranchId)->pluck('id');
    $stockFromMovements = 0;
    if (!$locationIds->isEmpty()) {
        $stockFromMovements = (float) StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $productId)
            ->sum(DB::raw("CASE WHEN direction='in' THEN quantity ELSE -quantity END"));
    }

    $fgStock = (float) FinishedGood::where('product_id', $productId)
        ->where('status', FinishedGood::STATUS_AVAILABLE)
        ->get()
        ->sum(fn($fg) => $fg->getRemaining());

    $product = Product::withoutGlobalScopes()->find($productId);
    $metaQty = (float) ($product->metadata['manufacturing_quantity'] ?? 0);

    return max(0, $stockFromMovements + $fgStock + $metaQty);
}
```

**New code:**
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
        ->whereNotIn('movement_type', [
            StockMovement::TYPE_PRODUCTION_CONSUMPTION,
            StockMovement::TYPE_PACKING_CONSUMPTION,
        ])
        ->sum('quantity');

    $stockOut = (float) StockMovement::whereIn('location_id', $locationIds)
        ->where('item_type', 'product')
        ->where('item_id', $productId)
        ->where('direction', 'out')
        ->whereIn('movement_type', [
            StockMovement::TYPE_SALE,
            StockMovement::TYPE_TRANSFER_OUT,
            StockMovement::TYPE_ADJUSTMENT_DECREASE,
            StockMovement::TYPE_DISTRIBUTION,
        ])
        ->sum('quantity');

    return max(0, (float)$stockIn - (float)$stockOut);
}
```

**Verification:** After applying this change, manually test:
1. Transfer Products page should show 60 units (the correct StockMovement-based value, not 64)
2. Dropdowns in `categories()` and `products()` should still display items (see step 3)

---

### 3. Update `ProductTransfer.categories()` to align with StockMovement-only logic
**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Purchases\ProductTransfer.php` (method at line ~189)

**Changes:**
- Remove the `$productIdsWithMeta` collection (which filters by `metadata['manufacturing_quantity'] > 0`)
- Keep only the movement-based check: products that have net positive StockMovement balance at the source branch
- This ensures the dropdown shows only products with genuine stock

**Old code (lines 189–219):**
```php
#[Computed]
public function categories(): \Illuminate\Support\Collection
{
    if (!$this->srcBranchId) return collect();

    // Get categories that have at least one product with available stock at source branch
    $locationIds = Location::where('branch_id', $this->srcBranchId)->pluck('id');

    // Product IDs with movement-based stock at this branch
    $productIdsWithMovements = collect();
    if ($locationIds->isNotEmpty()) {
        $productIdsWithMovements = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->selectRaw('item_id, SUM(CASE WHEN direction=\'in\' THEN quantity ELSE -quantity END) as bal')
            ->groupBy('item_id')
            ->having('bal', '>', 0)
            ->pluck('item_id');
    }

    // Product IDs with manufacturing_quantity metadata > 0 (not yet converted to movements)
    $productIdsWithMeta = Product::withoutGlobalScopes()
        ->where('status', 'active')
        ->whereNotNull('metadata')
        ->get(['id', 'metadata'])
        ->filter(fn($p) => (float)(($p->metadata ?? [])['manufacturing_quantity'] ?? 0) > 0)
        ->pluck('id');

    $allProductIds = $productIdsWithMovements->merge($productIdsWithMeta)->unique();

    if ($allProductIds->isEmpty()) {
        return collect(); // "No stock at this branch" will show
    }

    return Category::withoutGlobalScopes()
        ->where('is_active', true)
        ->whereHas('products', fn($q) => $q->withoutGlobalScopes()
            ->where('status', 'active')
            ->whereIn('id', $allProductIds))
        ->orderBy('name')
        ->get(['id', 'name']);
}
```

**New code:**
```php
#[Computed]
public function categories(): \Illuminate\Support\Collection
{
    if (!$this->srcBranchId) return collect();

    $locationIds = Location::where('branch_id', $this->srcBranchId)->pluck('id');

    // Product IDs with movement-based stock at this branch (net positive balance)
    $productIdsWithStock = collect();
    if ($locationIds->isNotEmpty()) {
        $productIdsWithStock = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->selectRaw('item_id, SUM(CASE WHEN direction=\'in\' THEN quantity ELSE -quantity END) as bal')
            ->groupBy('item_id')
            ->having('bal', '>', 0)
            ->pluck('item_id');
    }

    if ($productIdsWithStock->isEmpty()) {
        return collect();
    }

    return Category::withoutGlobalScopes()
        ->where('is_active', true)
        ->whereHas('products', fn($q) => $q->withoutGlobalScopes()
            ->where('status', 'active')
            ->whereIn('id', $productIdsWithStock))
        ->orderBy('name')
        ->get(['id', 'name']);
}
```

**Verification:** After applying this change, navigate to Transfer Products and verify that the category dropdown only shows categories with products that have stock via StockMovement (not metadata).

---

### 4. No changes needed to `ProductTransfer.products()`
**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Purchases\ProductTransfer.php` (method at line ~222)

**Rationale:** This method already calls `$this->getSourceBalance($p->id)` for each product, which will now use the StockMovement-only logic from step 2. No changes are required here.

---

## Verification Plan

### Before Testing
1. Note the current available quantity for "100 ml Tukur Azmud Oil" in both locations:
   - **Manufacturing Products** page: currently shows 64 units
   - **Transfer Products** page (Oil Manufacturing branch): currently shows 60 units
   
2. Query the StockMovement table directly to understand the ground truth:
   ```sql
   SELECT 
       'in' as direction,
       movement_type,
       SUM(quantity) as total
   FROM stock_movements
   WHERE item_id = (SELECT id FROM products WHERE name = '100 ml Tukur Azmud Oil')
     AND item_type = 'product'
   GROUP BY direction, movement_type
   UNION ALL
   SELECT 
       'out' as direction,
       movement_type,
       SUM(quantity) as total
   FROM stock_movements
   WHERE item_id = (SELECT id FROM products WHERE name = '100 ml Tukur Azmud Oil')
     AND item_type = 'product'
   GROUP BY direction, movement_type;
   ```
   This tells us what the "correct" value should be.

### After Applying All Changes
1. **Clear browser cache and reload** both pages to pick up the updated component code.

2. **Verify Manufacturing Products page** (`/admin/manufacturing/products`):
   - Navigate to the page
   - Select the same branch (Oil Manufacturing)
   - Confirm that "100 ml Tukur Azmud Oil" now shows the same quantity as Transfer Products (60 units, not 64)
   - **Expected outcome:** 60 units

3. **Verify Transfer Products page** (`/admin/purchases/transfer`):
   - Select source branch: "Oil Manufacturing"
   - Select category: "Tukur Azmud Oil"
   - Select product: "100 ml Tukur Azmud Oil"
   - Confirm the "Available at Source" field shows 60 units (unchanged, but now correctly aligned with Manufacturing page)
   - **Expected outcome:** 60 units

4. **Verify dropdown consistency** (step 3 changes):
   - On Transfer Products page, source branch "Oil Manufacturing"
   - The category dropdown should show only categories with products that have stock from StockMovement
   - Categories with only metadata['manufacturing_quantity'] (no movements) should NOT appear
   - **Expected outcome:** Only products with real stock movements appear

5. **Verify transfer execution still works**:
   - Attempt to transfer 50 units of "100 ml Tukur Azmud Oil" from one branch to another
   - Confirm the transfer succeeds
   - Verify that the source branch's available quantity decreases by 50 (from 60 to 10)
   - Verify that the destination branch's available quantity increases by 50
   - **Expected outcome:** Transfer succeeds, quantities are correctly deducted and added

6. **Test edge cases:**
   - Try to transfer more than available (e.g., 61 units when only 60 exist): should fail with validation error
   - Transfer the exact remaining amount: should succeed
   - Check that the Manufacturing Products page reflects the new stock after transfer

---

## Risk Assessment
- **Low Risk**: The changes consolidate three incompatible sources into one (StockMovement), which is already the authoritative ledger. The risk of breaking existing functionality is low because StockMovement is already being used; we're just removing the redundant sources.
- **Data Integrity**: No data is deleted or modified; only the calculation logic changes. Historical movements remain unchanged.
- **Rollback Path**: If issues arise, revert the three method changes to restore the original `max($mfrQty, $stockQty)` logic.

---

## Follow-Up Tasks (Post-Implementation)
1. **Deprecate metadata['manufacturing_quantity']**: Once this is confirmed working, consider removing all writes to this field and marking it for deletion in a future migration.
2. **Deprecate FinishedGood** (if it's only used for this calculation): Review if FinishedGood is used elsewhere; if not, it can be removed.
3. **Add logging/audit**: Consider adding a log entry when a transfer converts metadata to a StockMovement (for troubleshooting).
4. **Test with other products**: Verify the same fix works for all manufactured products (oils, books, clothes, etc.) to ensure no edge cases exist.
