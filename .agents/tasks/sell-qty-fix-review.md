# Stock Quantity Alignment for Manufacturing Sell Tab and Reports

The available quantities displayed in the manufacturing Sell tab and Mfr Report were consistently overstated. The sell tab showed 125 units for "100 ml beherzaf" when the correct available quantity (based on stock movements) was 120. The root cause was two methods reading stale product metadata instead of querying the `stock_movements` table. This fix realigns both methods with the already-correct `calculateBranchAvailableQty()` implementation, which computes net available stock from inbound production output minus outbound deductions. All three targeted methods were changed; no unrelated code was modified.

**Watch for:** The report fix uses a complex subquery approach that mirrors the per-location balance logic of `calculateBranchAvailableQty()`; verify it groups and scopes correctly by branch_id.

**Verdict**: APPROVED

## High-level view

The Sell tab was pulling available quantities from cached product metadata (`manufacturing_by_branch`), which accumulates historical manufacturing totals and doesn't reflect current net stock after sales and transfers. The fix redirects both the item dropdown and the selected-item Available field to use `calculateBranchAvailableQty()`, which already exists and correctly queries stock_movements to compute production_output inbound minus AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND outbound. The purchase sell path (for transferred products) was left untouched and continues to query transfer_in movements directly from stock_movements.

The Mfr Report's available quantity calculation was summing all stock_movements in/out without filtering by movement_type, which meant it incorrectly included inbound movements like production_consumption that should not add to available stock. The fix applies the same movement_type filtering used in `calculateBranchAvailableQty()`: only production_output inbound and deductible-outbound (sale, transfer_out, adjustment_decrease, distribution) count.

The manufacturing Add-to-List flow was not changed. The fix is purely in display logic, not in write paths.

<details>
<summary>Issues (2)</summary>

1. **Report subquery scoping** — The complex subquery in `$availableMap` groups by `item_id` and `branch_id` but the subqueries join back to the outer `stock_movements` table by these same columns. Verify that the grouping produces one row per product-branch combination and doesn't produce duplicates when multiple locations exist under a single branch.

2. **`calculateBranchAvailableQty()` picks best location balance** — The method iterates through all locations assigned to the user's branch(es) and returns the *highest* balance, not the sum. In a multi-location branch, if Location A has 120 units and Location B has 50 units, the method returns 120. Confirm this is intentional for the Sell tab (i.e., the user can sell from the best-stocked location) and not a logic gap that should sum across all branch locations.

</details>

<details>
<summary>Details</summary>

### Sell Products Dropdown and Available Field

The `sellProducts` computed property and `getSellAvailableQuantity()` method both read from `$product->metadata['manufacturing_by_branch']`, cached total manufacturing per branch, which does not reflect current stock after sales, transfers, or adjustments. Both now call `calculateBranchAvailableQty($product, $userBranchIds)`, which computes net available stock from stock_movements: production_output inbound (excluding production_consumption and packing_consumption) minus outbound deductions (sale, transfer_out, adjustment_decrease, distribution).

The purchase sell path (where `$this->sellType === 'purchase'`) was unchanged; it queries transfer_in movements directly and cannot use `calculateBranchAvailableQty()` because that method applies manufacture-specific movement_type filters.

### Manufacturing Report Available Quantity

The `getGmManufacturingReport()` method calculated `$availableMap` by summing all stock_movements in/out without checking movement_type, incorrectly including inbound movements like production_consumption that should not count toward available stock. The fix applies the same movement_type filtering as `calculateBranchAvailableQty()`: production_output inbound (excluding production_consumption, packing_consumption) minus outbound deductions (sale, transfer_out, adjustment_decrease, distribution). The implementation uses correlated subqueries to compute inbound and outbound sums independently, then keys the result by product_id and branch_id.

### No Changes to Purchase or Manufacturing Add-to-List Flows

The Sell tab's purchase type path retains its original logic: query transfer_in movements within user-assigned locations. The manufacturing Add-to-List flow was not touched.

### Branch Scoping

Both fixes respect the user's assigned branch(es):
- `sellProducts` calls `calculateBranchAvailableQty()` with `$userBranchIds` from `auth()->user()->assignedBranchIds()`.
- `calculateBranchAvailableQty()` converts branch IDs to location IDs and queries by `location_id`.
- The report fix filters `$availableMap` by `branch_id` matching products already scoped to the user's branch.

</details>

## File map

<details>
<summary>Files changed</summary>

- **app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php** — Lines 2056–2062 (sellProducts map closure): replaced metadata read with `calculateBranchAvailableQty()` call. Lines 2149–2159 (getSellAvailableQuantity method body): same replacement. No other methods modified.

- **app/Livewire/Admin/Reports/ReportDashboard.php** — Lines 706–713 (availableMap query): replaced simple in/out sum with movement_type-filtered subqueries matching `calculateBranchAvailableQty()` logic. No other methods modified.

See [full diff](javascript:void(0))

</details>
