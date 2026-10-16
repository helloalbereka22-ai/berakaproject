# Manufacturing Sell Quantity Fix Plan

## Problem Summary

The available quantity displayed in the manufacturing Sell tab (and related reports) shows stale/accumulated values instead of the correct net available stock. The root cause is that two methods read from product metadata (`manufacturing_by_branch`) instead of the source-of-truth: `stock_movements` table.

### Issues Identified

1. **Sell tab item dropdown** shows wrong available qty (e.g., 125 instead of 120)
2. **Sell tab Available field** shows wrong qty when item is selected
3. **Manufacturing report (Mfr Report)** shows wrong quantities (e.g., 138 instead of 120)

### Example
- Product: "100 ml beherzaf" in branch "bilal" (Bazar)
- Manufacturing list (correct): Manufactured Qty = 120
- Sell tab item dropdown (wrong): Shows 125.000
- Sell tab Available field (wrong): Shows 125
- Mfr Report (wrong): Shows 138

---

## Source of Truth Formula

Available Quantity = `production_output (in)` - `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND (out)`

Where `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` includes:
- `sale`
- `transfer_out`
- `adjustment_decrease`
- `distribution`

**See** `app/Models/StockMovement.php` lines 52-56 for the constant definition.

---

## Methods Already Correct (DO NOT CHANGE)

These methods already use `stock_movements` and are the correct implementation:

1. **`calculateBranchAvailableQty(Product $product, array $userBranchIds)`**
   - Location: `ManufacturingUserDashboard.php`, lines 1957-2001
   - Reads: `stock_movements` with `direction='in'` minus `direction='out'`
   - Filters in/out correctly using `AVAILABLE_QTY_EXCLUDED_INBOUND` and `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` constants
   - CORRECT — used by `allSellProducts` and `filteredSellProducts`

2. **`allSellProducts` (Computed property)**
   - Location: `ManufacturingUserDashboard.php`, lines 1853-1875
   - Calls `calculateBranchAvailableQty()` — CORRECT

3. **`filteredSellProducts` (Computed property)**
   - Location: `ManufacturingUserDashboard.php`, lines 1877-1907
   - Calls `calculateBranchAvailableQty()` — CORRECT

4. **`resolveAvailableQty(Product $product)` (private method)**
   - Location: `ManufacturingUserDashboard.php`, lines 2071-2107
   - Uses `stock_movements` with proper direction and movement_type filtering — CORRECT
   - Used internally but not the problem source

5. **Blade template** (`user-dashboard.blade.php`)
   - Already calls `$this->calculateBranchAvailableQty(...)` directly for displaying Available field — CORRECT

---

## Files and Methods to Fix

### File 1: `ManufacturingUserDashboard.php`

#### Issue 1: `sellProducts` Computed Property (lines 2006-2062)

**Current behavior** (WRONG):
- Reads from `$meta['manufacturing_by_branch']` (accumulated/stale metadata)
- Lines 2053-2060 use metadata instead of stock_movements

**Fix**:
Replace the manufactured-products section (lines 2053-2062) to call `calculateBranchAvailableQty()` instead of reading metadata directly.

**Exact change**:
- **Delete lines 2053-2062** (the map function that reads `manufacturing_by_branch`)
- **Replace with**: Call `calculateBranchAvailableQty()` for each product, same pattern as `allSellProducts`

**Verification**:
```bash
npm run artisan -- tinker
# In tinker:
$user = User::where('email', 'hebesha@gmail.com')->first();
auth()->setUser($user);
$dashboard = new \App\Livewire\Admin\Manufacturing\ManufacturingUserDashboard();
$dashboard->filterBusinessId = '...';
$dashboard->filterBranchId = '...';
$dashboard->sellCategoryId = (id of "Beherzaf Oil");
$dashboard->sellType = 'product';
dd($dashboard->sellProducts);
# Confirm each product->available_qty matches stock_movements net (120 for "100 ml beherzaf")
```

---

#### Issue 2: `getSellAvailableQuantity()` Method (lines 2116-2168)

**Current behavior** (WRONG):
- Lines 2153-2168 read from `$meta['manufacturing_by_branch']` instead of stock_movements
- This is the method called when user selects an item in the sell form

**Fix**:
Replace lines 2153-2168 (the section that reads manufactured_by_branch metadata) to call `calculateBranchAvailableQty()` instead.

**Exact change**:
- **Delete lines 2153-2168**
- **Replace with**: 
  ```php
  // Use the correct calculation (stock_movements)
  return $this->calculateBranchAvailableQty($product, $userBranchIds);
  ```

**Verification**:
After fix, the Available field in the Sell tab will match the stock_movements net available qty.

---

### File 2: `ReportDashboard.php`

#### Issue 3: `getGmManufacturingReport()` - Available Quantity Calculation (lines 706-713)

**Current behavior** (WRONG):
- Lines 706-713 sum ALL stock_movements directions (in - out) **without filtering movement_type**
- This includes movements that shouldn't count toward "available for sale"
- Example: includes `production_consumption` movements which are inbound-type but don't add to available stock

**Fix**:
Modify the `$availableMap` query (lines 706-713) to apply the same movement_type filtering as `calculateBranchAvailableQty()`.

**Exact change**:
Replace the entire `$availableMap` construction with proper direction and movement_type filtering:

```php
// BEFORE (lines 706-713):
$availableMap = DB::table('stock_movements')
    ->where('item_type', 'product')
    ->whereIn('stock_movements.item_id', $productionOutputs->pluck('product_id')->unique()->toArray())
    ->select([
        'item_id as product_id',
        'branch_id',
        DB::raw('SUM(CASE WHEN direction = "in" THEN quantity ELSE -quantity END) as available_qty'),
    ])
    ->groupBy('item_id', 'branch_id')
    ->get()
    ->keyBy(fn($r) => $r->product_id . '_' . $r->branch_id);

// AFTER (apply movement_type filtering):
$availableMap = DB::table('stock_movements')
    ->where('item_type', 'product')
    ->whereIn('stock_movements.item_id', $productionOutputs->pluck('product_id')->unique()->toArray())
    ->select([
        'item_id as product_id',
        'branch_id',
        DB::raw('
            (
                SELECT COALESCE(SUM(quantity), 0) FROM stock_movements sm1
                WHERE sm1.item_id = stock_movements.item_id
                  AND sm1.branch_id = stock_movements.branch_id
                  AND sm1.item_type = "product"
                  AND sm1.direction = "in"
                  AND sm1.movement_type NOT IN ("' . implode('", "', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND) . '")
            )
            -
            (
                SELECT COALESCE(SUM(quantity), 0) FROM stock_movements sm2
                WHERE sm2.item_id = stock_movements.item_id
                  AND sm2.branch_id = stock_movements.branch_id
                  AND sm2.item_type = "product"
                  AND sm2.direction = "out"
                  AND sm2.movement_type IN ("' . implode('", "', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND) . '")
            )
        as available_qty
        '),
    ])
    ->groupBy('item_id', 'branch_id')
    ->get()
    ->keyBy(fn($r) => $r->product_id . '_' . $r->branch_id);
```

**Alternatively (simpler)**: Extract this logic into a helper method in a service class and reuse it, but for this fix, inline is acceptable.

**Verification**:
```bash
# Visit http://localhost/albarekaaaaaa/public/admin/reports (select tab "GM Manufacturing")
# Filter by branch "bilal", user "hebesha@gmail.com"
# Confirm "100 ml beherzaf" shows:
# - Manufactured Qty (Available) = 120 (matches stock_movements net)
# - Not 138 or 125
```

---

## Implementation Order

1. **Fix `sellProducts` in ManufacturingUserDashboard.php** (lines 2053-2062)
   - This fixes the item dropdown
   - Verify: Sell tab item dropdown shows correct qty

2. **Fix `getSellAvailableQuantity()` in ManufacturingUserDashboard.php** (lines 2153-2168)
   - This fixes the Available field when user selects an item
   - Verify: Sell tab Available field shows correct qty

3. **Fix `getGmManufacturingReport()` in ReportDashboard.php** (lines 706-713)
   - This fixes the Mfr Report quantities
   - Verify: Reports page shows correct Quantity column

---

## Testing Checklist

- [ ] Login as "bilal" user (hebesha@gmail.com)
- [ ] Go to Manufacturing > Sell tab
- [ ] Select "Beherzaf Oil" category
- [ ] Verify item dropdown shows "100 ml beherzaf (120.000)" — NOT (125.000)
- [ ] Click the item to select it
- [ ] Verify Available field shows "120.000" — NOT "125"
- [ ] Go to Reports > GM Manufacturing
- [ ] Filter by branch "bilal"
- [ ] Verify "100 ml beherzaf" row shows:
  - Quantity column: 120 (NOT 138, NOT 125)
- [ ] Repeat for other products and branches to confirm no regression

---

## Notes

- No data migration needed — only code logic changes
- The fix aligns the Sell tab and Reports with the already-correct `calculateBranchAvailableQty()` method
- All changes are localized to quantity display; no changes to business logic, sales processing, or data structure
- The `production_output` and `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` constants in StockMovement are the single source of truth
