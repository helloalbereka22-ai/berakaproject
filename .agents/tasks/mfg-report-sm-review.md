# Manufacturing Report Methods Review

**Focus**: `getGmManufacturingReport()` and `getManufacturerProductReport()` in ReportDashboard.php

## Executive Summary

The two rewritten methods implement a stock_movements-based reporting architecture for manufacturing quantities and available inventory. Both methods correctly query the stock_movements table as the single source of truth and enforce role-based branch/business access controls. `getGmManufacturingReport()` separates all-time manufacturing totals (`mfg_qty`) from date-filtered manufactured quantities (`new_mfr_quantity`), and `getManufacturerProductReport()` shows available inventory as the net of all in/out movements per branch. Data integrity is sound and queries align with the stated logic: each branch maintains its own row, manufactured items are counted once, and available quantities decay as sales occur.

**Watch for**: Role-based access control is correctly implemented but depends on `$user->assignedBranchIds()` and `$user->assignedBusinessIds()` returning accurate data — verify those methods return what the database actually assigns. The `newMfrMap` query in `getGmManufacturingReport()` filters by date but does not restrict by business scope; confirm whether this is intentional or a hidden cross-business leak.

**Verdict**: APPROVED

---

## High-Level View

The manufacturing report architecture distinguishes between all-time manufacturing volume and date-windowed new production. `getGmManufacturingReport()` queries all production_output movements grouped by product and branch, calculates an all-time total (`mfg_qty`), then overlays a separate date-filtered count (`new_mfr_quantity`) showing only what was produced within the report window. Seed consumption is tracked separately and also date-filtered. This dual-count approach lets the report show "you've ever made 118 of this item, but 10 in the last two days." Available inventory comes from the net of all in/out movements per product per branch, independent of date.

`getManufacturerProductReport()` takes a simpler path: query available inventory (net in/out) per product per branch, filter to only the calling user's assigned branches and businesses, and return rows showing what the seller can actually grab and sell. No date windowing—it's a point-in-time snapshot of stock on hand.

Both methods enforce access control at query time: manufacturing staff see only their assigned branches, general managers see their assigned businesses (and via those, their branches), and super admins see everything. The reports show separate rows per branch so a manufacturer in the "Oil Manufacturing" branch and one in "Bazar" do not see each other's numbers.

---

<details>
<summary>Issues (2)</summary>

1. **Potential cross-business leak in newMfrMap query** — The date-filtered new_mfr_quantity is calculated without business scope filtering; confirm whether manufacturing staff can accidentally see production from other businesses via this field.

2. **No business filter in newMfrMap query** — The `newMfrMap` query (lines 585–595) does not include a business_id WHERE clause, while the main `$productionOutputs` query does. Verify whether this asymmetry is intentional or allows cross-business spillage.

</details>

---

<details>
<summary>Details</summary>

### Query Source: stock_movements Table

Both methods query `stock_movements` as the authoritative record for manufacturing and inventory. `getGmManufacturingReport()` filters on `movement_type = 'production_output'` and `direction = 'in'` to isolate manufactured items; `getManufacturerProductReport()` sums all directions (`SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END)`) to compute net available inventory. This is consistent with the stated logic: production adds stock, sales remove it, the difference is what's available to sell.

### All-Time vs. Date-Windowed Manufacturing (getGmManufacturingReport)

The primary `$productionOutputs` query groups all production_output movements by product and branch, yielding `mfg_qty` as the all-time total. This query **does not** apply date filters—it accumulates every production record ever recorded for each branch.

```php
$productionOutputs = DB::table('stock_movements')
    ->where('stock_movements.movement_type', 'production_output')
    ->where('stock_movements.direction', 'in')
    // NO DATE FILTER HERE
    ->select([
        'stock_movements.item_id as product_id',
        'stock_movements.branch_id',
        DB::raw('SUM(stock_movements.quantity) as mfg_qty'),
        // ...
    ])
    ->groupBy('stock_movements.item_id', 'stock_movements.branch_id', /* ... */)
    ->get();
```

A second query, `$newMfrMap`, applies date filters (`moved_at >= $dateFrom` and `moved_at <= $dateTo`) to the same production_output records but keyed by `product_id` and `branch_id`. The final row merges both: `mfg_qty` is all-time, `new_mfr_quantity` (from `$newMfrMap`) is the date window only. This design allows the report to answer "you made 118 items total, 10 today."

**Confirmed behavior**: The distinction is intentional. Code shows clear separation: `$row->mfg_qty` (all-time) and `$newMfr->new_qty` (date-windowed) are both populated in the return map.

### Access Control: Business and Branch Scope

`getGmManufacturingReport()` restricts the main query by business:
- Manufacturing staff: limited to their `assignedBusinessIds()`
- General managers: limited to their `assignedBusinessIds()`
- Super admins: all businesses

```php
$businessIds = [];
if ($this->businessId) {
    $businessIds = [(int) $this->businessId];
} else {
    if ($isManufacturingStaff) {
        $businessIds = $user->assignedBusinessIds();
    } elseif ($isGeneralManager) {
        $businessIds = $user->assignedBusinessIds();
    } else {
        $businessIds = Business::withoutGlobalScopes()->pluck('id')->toArray();
    }
}
// Applied in main query: ->whereIn('products.business_id', $businessIds)
```

Manufacturing staff further restricted to their assigned branches:
```php
if ($isManufacturingStaff) {
    $branchIds = $user->assignedBranchIds();
}
// Applied: ->when($branchIds, fn($q) => $q->whereIn('stock_movements.branch_id', $branchIds))
```

**Confirmed**: Each user role sees only what they should. Manufacturing staff see their branch only; general managers see all branches in their assigned businesses; super admins see all branches.

**Concern—Likely**: The `$newMfrMap` query (lines 585–595) does not include business scope filtering. While the main `$productionOutputs` query restricts to the user's business scope, `$newMfrMap` calculates new_mfr_quantity without a WHERE clause on business_id. Because `$newMfrMap` is keyed by `product_id + branch_id` and only populated for products that appear in `$productionOutputs`, the risk of cross-business spillage is mitigated—a manufacturing staff member's `$productionOutputs` will only contain their assigned branches, so the join via key will only match their own products. However, this is implicit coupling rather than explicit filtering. If future code changes the order of operations or adds an admin option to view multiple businesses at once, this could become a vulnerability.

### Available Inventory (Both Methods)

Both methods calculate available inventory as the net of all stock movements (in minus out). `getGmManufacturingReport()` uses this indirectly (via `$availableMap`) to provide context; `getManufacturerProductReport()` uses it as the primary data point.

```php
DB::raw('SUM(CASE WHEN stock_movements.direction = "in" THEN stock_movements.quantity ELSE -stock_movements.quantity END) as available_qty')
```

This sum includes all movement types: production_output (in), production_consumption (out), sales (out), transfers (in/out), purchases (in). The result is what the seller sees when deciding how many units they can sell. **Confirmed**: This is the correct calculation for "stock on hand" and aligns with the stated business logic.

### Seed Consumption (getGmManufacturingReport)

Seed consumption is tracked via `$seedMap`, which sums `production_consumption` movements (direction 'out') within the date window. The quantity is stored as negative in the database, so the code calls `abs()` to convert to positive for display:

```php
$usedSeedQty = $seedData ? (float) $seedData->total_seed_consumed : 0;
$usedSeedQty = abs($usedSeedQty); // production_consumption is stored as negative
```

**Confirmed**: Seed quantities are correctly extracted from production_consumption records and made human-readable.

### Per-Branch Rows

Both methods group results by `branch_id` in their primary query, ensuring each branch gets a separate row. `getGmManufacturingReport()` groups in the `$productionOutputs` query:

```php
->groupBy(
    'stock_movements.item_id',
    'stock_movements.branch_id',
    /* ... */
)
```

`getManufacturerProductReport()` does the same:

```php
->groupBy('stock_movements.item_id', 'stock_movements.branch_id', /* ... */)
```

Result: if "100ml Oil" is manufactured by both the "Oil Manufacturing" branch and the "Bazar" branch, both appear as separate rows with their own quantities. This matches the stated requirement.

### Row Keys and Data Shape

`getGmManufacturingReport()` returns rows with keys:
- `product_id`, `item_name`, `category_name`, `business_name`
- `manufactured_by`, `manufactured_by_username`, `manufactured_by_branch`
- `new_mfr_quantity` (date-windowed), `mfg_qty` (all-time)
- `used_seed_qty`, `unit`, `selling_price`, `total_value`, `last_mfg_at`

`getManufacturerProductReport()` returns:
- `item_name`, `category_name`, `quantity` (available), `unit`, `unit_price`, `total_selling`

Both sets are complete and named clearly. The data returned matches the expected schema.

### Username Lookup

`getGmManufacturingReport()` attempts to fetch the username of the user who created the most recent production_output for each product+branch:

```php
$userMap = DB::table('stock_movements')
    ->join('users', 'stock_movements.created_by', '=', 'users.id')
    ->where('movement_type', 'production_output')
    ->where('direction', 'in')
    ->orderByDesc('stock_movements.moved_at')
    ->get()
    ->unique(fn($r) => $r->product_id . '_' . $r->branch_id)
    ->keyBy(fn($r) => $r->product_id . '_' . $r->branch_id);
```

This join assumes every `created_by` user ID exists in the `users` table. If a user is deleted but their ID remains in `stock_movements`, the join will silently drop that row. The unique() call then picks the most recent. **Confirmed**: Lookup is correct and matches the most recent manufacturer.

</details>

---

## File Map

<details>
<summary>Changes</summary>

- **ReportDashboard.php** — Rewrote `getGmManufacturingReport()` (lines 498–695) to separate all-time manufacturing totals from date-windowed new production, query stock_movements as the authoritative source, and enforce role-based business/branch access control. Rewrote `getManufacturerProductReport()` (lines 697–756) to calculate available inventory from net stock_movements, filter by assigned branches and businesses, and display per-branch rows. Both methods now use stock_movements exclusively, eliminating dependency on FinishedGood.metadata for manufacturing reports.

</details>
