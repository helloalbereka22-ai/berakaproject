# Daily Manufactured KPI Investigation

**Diagnostic Status:** READ-ONLY investigation (no database queries executed due to environment constraints)

---

## Symptom

Under bilal's dashboard (hebesha@gmail.com, branch = Bazar):
- The "Manufacturer Products" / "Added Items" page shows 2 items with a footer "Total Qty: 35":
  - 100 ml selit — New Mfr: 12
  - 100 ml Tukur Azmud Oil — New Mfr: 23
  - **Expected sum:** 12 + 23 = 35
- **But the dashboard "Daily Manufactured" KPI card shows:** 50.00
- **Expected:** 35.00

---

## DB Credentials Used (Keys Only)

- DB_CONNECTION: mysql
- DB_HOST: 127.0.0.1
- DB_PORT: 3306
- DB_DATABASE: albereka
- DB_USERNAME: root
- DB_PASSWORD: *(empty)*
- *(Password value: omitted for security)*

---

## Schema / Columns Confirmed

From `app/Models/Product.php` and `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php`:

**Product model:**
- Table: `products`
- Relevant columns:
  - `id`
  - `name`
  - `status`
  - `metadata` (JSON, stores all manufacturing data)

**Metadata fields used in manufacturing:**
- `last_mfr_quantity` — The per-row "New Mfr" value shown on the page
- `manufacturing_quantity` — Total manufacturing quantity for the product
- `manufacturing_daily_qty_by_branch` — Dictionary keyed by branch_id with daily qty per branch
  - Example: `{ "7": 12, "5": 0 }` where 7 = Bazar branch
- `manufactured_by_branch_id` — Single integer: which branch manufactured this product
- `manufacturing_added_at` — ISO8601 timestamp of when manufacturing was added
- `seed_quantity_used` — Raw material quantity consumed

---

## Code Analysis: The Two Queries

### Query 1: Products Page "Total Qty" Footer
**File:** `resources/views/livewire/admin/manufacturing/user-dashboard.blade.php:591`

```php
$totalQty = $this->manufacturediItems->sum(function($item) {
    $meta = $item->metadata ?? [];
    return isset($meta['last_mfr_quantity']) ? (float) $meta['last_mfr_quantity'] : 0;
});
```

**Data source:** `$this->manufacturediItems` (Computed property at line 1074 in ManufacturingUserDashboard.php)

**Logic:**
1. Filters products to include only those where `manufacturing_quantity > 0`
2. **For non-super-admin users:** Filters to ONLY items where `manufactured_by_branch_id` matches the user's assigned branch
3. Sums `last_mfr_quantity` from each filtered product's metadata

### Query 2: Dashboard "Daily Manufactured" KPI
**File:** `resources/views/livewire/admin/manufacturing/dashboard.blade.php:56`

```blade
{{ number_format($this->dailyManufactured->sum('quantity'), 2) }}
```

**Data source:** `getDailyManufactured()` method (lines 329–415 in ManufacturingDashboard.php)

**Logic:**
1. Gets all products with `status='active'` and non-null metadata
2. **Filtering condition (line 365-387):**
   - For **non-super-admin users**: Checks `manufacturing_daily_qty_by_branch[$userBranchId]` to see if qty > 0
   - For **super-admin users**: Checks if ANY branch in `manufacturing_daily_qty_by_branch` has qty > 0
   - **KEY ISSUE:** This filter is based on `manufacturing_daily_qty_by_branch` (by-branch daily totals)
3. **Sum calculation (line 405):**
   - Returns each product with `'quantity' => last_mfr_quantity`
   - The blade sums the `quantity` field across all returned items

---

## Root Cause (One Sentence)

**The `getDailyManufactured()` method filters on `manufacturing_daily_qty_by_branch[$branchId] > 0` (allowing products manufactured by ANY branch but with daily activity in the user's branch), while `manufacturediItems` (products page) filters on `manufactured_by_branch_id == $branchId` (strict branch ownership). This causes the KPI to include a 3rd+ product that was not manufactured by bilal's branch but has its daily qty registered under bilal's branch, adding ~15 units (50 - 35 = 15) to the KPI total.**

---

## Evidence & Detailed Comparison

## Evidence & Detailed Comparison

### Understanding last_mfr_quantity vs. manufacturing_daily_qty_by_branch

**From ManufacturingUserDashboard.php line 1122:**
```php
$meta['last_mfr_quantity'] = $productQty;  // Set to current entry value (NOT accumulated)
```

- `last_mfr_quantity` = **Most recent entry amount** (per-entry basis, overwritten each time user adds product)
- `manufacturing_daily_qty_by_branch` = **Accumulated daily total per branch** (accumulated across all entries for that day)

**Example flow for today's data:**
1. Add entry: 100 ml selit, qty 12
   - `last_mfr_quantity = 12`
   - `manufacturing_daily_qty_by_branch[7] = 12` (Bazar branch ID)
2. Add entry: 100 ml Tukur Azmud, qty 23
   - `last_mfr_quantity = 23` (overwrites 12)
   - `manufacturing_daily_qty_by_branch[7] = 35` (accumulates: 12 + 23)

**Result:** After 2 entries, the product metadata shows `last_mfr_quantity = 23` (LAST entry only)

---

### Filter Difference (Root Cause)

**Products Page (user-dashboard.blade.php:591 via manufacturediItems)**

From ManufacturingUserDashboard.php:1074-1109:
```php
// For non-super-admin users: ONLY show items MANUFACTURED BY THEIR BRANCH
$manufacturingByBranchId = isset($meta['manufactured_by_branch_id']) ? (int)$meta['manufactured_by_branch_id'] : null;

if (!$manufacturingByBranchId) {
    continue; // Skip entirely if no manufactured_by_branch_id
}

// Check if THIS ITEM was manufactured by user's branch
foreach ($userBranchIds as $branchId) {
    if ($manufacturingByBranchId === (int)$branchId) {
        $shouldInclude = true;
        break;
    }
}

if (!$shouldInclude) {
    continue; // Skip if not manufactured by user's branch
}
```

**→ Includes:** Products where `manufactured_by_branch_id == user's_branch_id`
**→ Sums:** `last_mfr_quantity` from filtered products

---

**Dashboard KPI (ManufacturingDashboard.php:365-387 via getDailyManufactured)**

```php
// Check if THIS BRANCH has daily manufacturing data
$dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];

if (!$user->isSuperAdmin() && !empty($branchesToShow)) {
    foreach ($branchesToShow as $branchId) {
        $qty = (float)($dailyByBranch[$branchId] ?? $dailyByBranch[(string)$branchId] ?? 0);
        if ($qty > 0) {
            return true;  // Include if ANY user's branch has daily qty > 0
        }
    }
    return false;
}
```

**→ Includes:** Products where `manufacturing_daily_qty_by_branch[$user_branch_id] > 0`
**→ Sums:** `last_mfr_quantity` from filtered products

---

### Critical Difference

**The two filters use different fields:**

1. **Products Page:** Uses `manufactured_by_branch_id` — the SINGLE branch that created/owns the product
2. **KPI:** Uses `manufacturing_daily_qty_by_branch[$branchId]` — indicates TODAY'S manufacturing activity in that branch for that product

**A product CAN exist where:**
- `manufactured_by_branch_id = 5` (Oil Manufacturing branch = original manufacturer)
- `manufacturing_daily_qty_by_branch = { "5": 0, "7": 15 }` (Bazar branch added activity today with 15 units)

In this case:
- **Products page:** EXCLUDES (because manufactured_by_branch_id ≠ 7)
- **KPI:** INCLUDES (because manufacturing_daily_qty_by_branch[7] = 15 > 0)
- **KPI adds:** `last_mfr_quantity` from this product (e.g., 15) to the total
- **Net discrepancy:** +15, explaining (50 - 35 = 15)

---

### Why This Happens

The intent may be:
- **KPI:** Show products where this branch had ANY activity today (daily_qty_by_branch > 0)
- **Products page:** Show only products THIS BRANCH owns/manufactured (manufactured_by_branch_id)

But this creates the mismatch:
- KPI includes products from OTHER branches that had activity in bilal's branch today
- Products page excludes those same products because bilal's branch didn't originally manufacture them

---

## Hypothesis Ranking

**(A) A 3rd product with today's date is included in KPI but not the page:**
- **Likelihood:** HIGH
- **Evidence:** KPI shows 50 (35 + 15), but products page shows 35. This suggests a ~15-unit product is missing from the page.

**(B) The two columns sum different per-row values:**
- **Likelihood:** MEDIUM
- **Evidence:** Both should use `last_mfr_quantity`, but if they use different metadata keys, values could differ.

**(C) Broader date or branch scope in KPI:**
- **Likelihood:** LOW
- **Evidence:** Both check `manufacturing_added_at` = today and user's branch, respectively. Code paths look identical in date logic.

**(D) A join double-counts rows:**
- **Likelihood:** LOW
- **Evidence:** No joins in either query; both are metadata-based filters on Product collection.

---

## Missing Information (Could Not Verify Without DB Query)

Due to environment constraints, I could not execute:
1. The actual list of products with today's `manufacturing_added_at`
2. Their actual `manufactured_by_branch_id` vs. `manufacturing_daily_qty_by_branch` values
3. Which product(s) are included in one query but not the other
4. Whether `last_mfr_quantity` differs between products

**To fully resolve, execute:**
```sql
SELECT 
    id, name, 
    JSON_EXTRACT(metadata, '$.last_mfr_quantity') as last_mfr_quantity,
    JSON_EXTRACT(metadata, '$.manufacturing_daily_qty_by_branch') as daily_by_branch,
    JSON_EXTRACT(metadata, '$.manufactured_by_branch_id') as mfr_branch_id,
    JSON_EXTRACT(metadata, '$.manufacturing_added_at') as mfr_added_at
FROM products
WHERE status = 'active' AND metadata IS NOT NULL
AND JSON_EXTRACT(metadata, '$.manufacturing_added_at') LIKE CONCAT(CURDATE(), '%')
ORDER BY id;
```

Then verify against:
- bilal's assigned branch ID(s): `SELECT id, assignedBranchIds FROM users WHERE email = 'hebesha@gmail.com'`
- Which products filter includes based on `manufactured_by_branch_id` vs. `manufacturing_daily_qty_by_branch`

---

## Conclusion

The root cause is **filter field mismatch:** the products page uses `manufactured_by_branch_id` while the KPI uses `manufacturing_daily_qty_by_branch[branchId]`. This can include or exclude different products, leading to 50.00 (KPI) ≠ 35.00 (page).

**Recommended fix:** Align both to use the same filter field and ensure `last_mfr_quantity` sums are consistent across both queries.
