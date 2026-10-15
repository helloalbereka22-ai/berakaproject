# Manufacturing Report Refactor: stock_movements as Single Source of Truth

## Problem Summary

The manufacturing report displays inconsistent quantities across three views:
- Super Admin report: 118 manufactured qty
- General Manager report: 361 manufactured qty  
- Manufacturer selling page: 86 available qty

Root cause: The three views read from different data sources (FinishedGood metadata, Product metadata, and metadata manufacturing_by_branch). Data de-synchronizes when the same product is modified or sold through different branches.

## Solution

Rewrite both manufacturing report methods to read exclusively from the `stock_movements` table, which is the authoritative ledger of all inventory transactions. This ensures all views display consistent numbers.

## Design Decisions

1. **Single source of truth**: `stock_movements` table only. No metadata-based manufacturing quantities.
   - Rationale: `stock_movements` is a transactional ledger with rows for every stock event. It is never overwritten; only appended to. Metadata fields are mutable and can become stale when updates happen out of sync.

2. **Key formulas from stock_movements**:
   - **Manufactured Qty (historical grand total, never decreases)**: `SUM(quantity)` WHERE `direction='in'` AND `movement_type='production_output'` GROUP BY `item_id`, `branch_id`. This is the cumulative manufactured qty ever, per branch.
   - **Available Qty (current stock, decreases when sold)**: `SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END)` for all movements on that `item_id` + `branch_id`. Equivalent: `in_qty - out_qty`.
   - **New Mfr Qty (qty added in current date filter)**: `SUM(quantity)` WHERE `direction='in'` AND `movement_type='production_output'` AND `moved_at BETWEEN date_from AND date_to` GROUP BY `item_id`, `branch_id`.
   - **Used Seed Qty**: `SUM(quantity)` WHERE `direction='out'` AND `movement_type='production_consumption'` GROUP BY `item_id`, `branch_id`.

3. **Access control** (unchanged logic, will be preserved):
   - Super Admin: sees all branches
   - General Manager: sees only their assigned business's branches
   - Manufacturing staff: sees only their assigned branches

4. **Row structure per branch**:
   - Each report row = one product per one branch (not aggregated across branches).
   - Super Admin and General Manager see separate rows for each branch.
   - Manufacturer (seller) sees aggregated available qty across their branches (for sell page).

## Implementation Plan

### File to modify
- `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Reports\ReportDashboard.php`

### Methods to rewrite

#### 1. Rewrite `getGmManufacturingReport(array $filters): \Illuminate\Support\Collection`

**File**: `app/Livewire/Admin/Reports/ReportDashboard.php`

**What to do**: Replace the current implementation (which mixes FinishedGood records and Product metadata) with a stock_movements-based query. The method must:
- Query `stock_movements` WHERE `item_type='product'` AND `movement_type='production_output'`
- Group by `product_id` and `branch_id` to get one row per product per branch
- Calculate `mfg_qty` (grand total manufactured) = `SUM(quantity)` for all production_output movements
- Calculate `new_mfr_quantity` = `SUM(quantity)` for production_output movements within the date filter window
- Calculate `used_seed_qty` = `SUM(quantity)` WHERE `movement_type='production_consumption'` for that product (summed across all movement directions)
- Join to `products`, `categories`, `branches`, and `users` tables to get display names
- Filter by business and branch scope based on user permissions
- Return `\Illuminate\Support\Collection` with keys: `product_id`, `item_name`, `category_name`, `business_name`, `manufactured_by`, `manufactured_by_username`, `manufactured_by_branch`, `new_mfr_quantity`, `mfg_qty`, `used_seed_qty`, `unit`, `selling_price`, `total_value`, `seed_name`, `seed_qty_used`, `seed_unit`, `last_mfg_at`

**Verification**: Run the project's manufacturing report test (if any exists) and verify the report displays consistent quantities when filtered by date range and branch. Check that Super Admin, General Manager, and Manufacturer roles all see consistent manufactured qty numbers for the same product+branch combination.

#### 2. Rewrite `getManufacturerProductReport(array $filters): array`

**File**: `app/Livewire/Admin/Reports/ReportDashboard.php`

**What to do**: Replace the current implementation to use stock_movements directly for available qty calculation. The method must:
- For manufacturing staff (can produce, cannot update business) and sellers: query stock_movements directly
- For each finished product (`type` IN `['finished_product', 'bottle_product']`):
  - Calculate available qty per branch = `SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END)` for all movement types on that `item_id` + `branch_id`
  - Aggregate available qty across the user's assigned branches: sum the per-branch available qty
- Return array with shape: `['rows' => Collection, 'totals' => ['quantity' => ..., 'total_selling' => ...]]`
- Row shape: `item_name`, `category_name`, `quantity` (available), `unit`, `unit_price` (selling_price), `total_selling`

**Rationale for change**: The sell page must show the same available qty as the stock_movements calculation. The old metadata-based calculation drifted when inventory operations (sales, transfers) were recorded in stock_movements but not synced back to metadata.

**Verification**: Run the manufacturer sell page and verify available qty matches the quantity shown in the "Manufactured Products" report after deducting sales.

### Pseudo-code for stock_movements queries

```
-- Manufactured Qty (per product per branch, historical grand total):
SELECT 
  item_id, 
  branch_id,
  SUM(quantity) as mfg_qty
FROM stock_movements
WHERE item_type='product' 
  AND movement_type='production_output'
  AND direction='in'
GROUP BY item_id, branch_id

-- New Mfr Qty (added in date range):
SELECT
  item_id,
  branch_id,
  SUM(quantity) as new_mfr_qty
FROM stock_movements
WHERE item_type='product'
  AND movement_type='production_output'
  AND direction='in'
  AND moved_at BETWEEN @dateFrom AND @dateTo
GROUP BY item_id, branch_id

-- Used Seed Qty (consumed in production):
SELECT
  item_id,
  SUM(quantity) as seed_consumed  -- direction='out'
FROM stock_movements
WHERE item_type='product'
  AND movement_type='production_consumption'
GROUP BY item_id

-- Available Qty (current stock, per product per branch):
SELECT
  item_id,
  branch_id,
  SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as available_qty
FROM stock_movements
WHERE item_type='product'
GROUP BY item_id, branch_id
```

### Testing checklist

- [ ] Super Admin views "Manufactured Products" report for the "Oil Manufacturing" branch with 10 manufactured units and sees "Manufactured Qty" = 10 (not 118 or 361)
- [ ] General Manager views the same report and sees the same consistent number per branch
- [ ] Manufacturer (manf@gmail.com) views the sell page and sees available qty matching what was manufactured minus what was sold
- [ ] After selling 2 units, the available qty decreases to 8 on all views
- [ ] Date filtering works: filtering to today shows only today's manufactured qty, not accumulated
- [ ] Branch filtering works: filtering to "Oil Manufacturing" branch shows only that branch's numbers
- [ ] Multiple products each show correct qty per their own branch production

## Notes

- Do NOT modify any other method in ReportDashboard.php (getRawMaterialDeductionReport, getStockChangesReport, etc.)
- Do NOT modify any blade views or UI logic
- Do NOT modify any models (StockMovement, Product, Branch)
- The existing row shape expected by the gm-manufacturing.blade.php view must be preserved exactly
- Assume stock_movements table has been populated with all historical data (no data loss on migration)
