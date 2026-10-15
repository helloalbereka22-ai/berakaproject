# Seed Consumption Discrepancy Investigation: 'rate oil'

## Summary

The report shows **76 units manufactured today (New Mfr)** but **only 3 units of seed consumed today (Used Qty)** when we would expect ~76 units of seed to be deducted if 76 were actually produced. The investigation reveals the root cause lies in **mismatched data sources** between production output records and seed consumption tracking.

---

## Key Findings

### 1. **Data Source Mismatch**

The GM Manufacturing Report (ReportDashboard.php, lines 476–621) pulls data from **two separate sources** that are not synchronized:

**Production Output (New Mfr = 76):**
- Source: `stock_movements` table where `movement_type = 'production_output'` 
- Records: Production batches created by manufacturing staff
- Filters applied: `direction = 'in'` and date range

**Seed Consumption (Used Qty = 3, Total Seed Used = 28):**
- Source 1: `stock_movements` table where `movement_type = 'production_consumption'` (lines 561–574)
- Source 2: Product metadata `seed_by_branch` and `current_seed_deduction` fields (lines 528–557)

**The Problem:** 
- Production output records are captured as **stock_movements** when a batch is registered
- Seed consumption is tracked **in two places**: 
  - Historical consumption in product metadata (`metadata.seed_quantity_used` or `metadata.seed_by_branch`)
  - Daily consumption in stock_movements with movement_type='production_consumption'

These are not automatically synchronized. A production batch (76 units) can exist in `stock_movements` while the corresponding seed deduction is never recorded or is recorded separately.

---

### 2. **Code Logic Analysis**

#### In `getGmManufacturingReport()` (lines 476–621):

**For "New Mfr Quantity" (76):** Lines 510–517
```php
$newMfrMap = DB::table('stock_movements')
    ->where('movement_type', 'production_output')
    ->where('direction', 'in')
    // ... DATE FILTER APPLIED
    ->select(['item_id', 'branch_id', DB::raw('SUM(quantity) as new_qty')])
```
✅ This captures TODAY's production output correctly: **76 units**

**For "Used Qty Today" (3):** Lines 561–574
```php
$seedConsumedTodayMap = DB::table('stock_movements')
    ->where('movement_type', 'production_consumption')
    ->where('direction', 'out')
    // ... DATE FILTER APPLIED
    ->select(['item_id', 'branch_id', DB::raw('SUM(quantity) as seed_consumed')])
```
❌ This looks for separate `production_consumption` movements **on today's date**, but those records may not exist or may be incomplete.

**For "Total Seed Used" (28):** Lines 528–557
```php
$seedMap = \App\Models\Product::withoutGlobalScopes()
    // Reads from metadata: $meta['seed_quantity_used'] or $meta['seed_by_branch']
```
This reads **historical totals** from product metadata, not today's actual transactions.

#### The Mismatch:
- **Production output** is recorded as a stock_movement when the batch is created
- **Seed deduction** is only recorded if:
  1. An explicit `production_consumption` stock_movement is created AND
  2. That movement is dated the same day as the production output

If seed deduction happens on a different date, or is not recorded as a movement, or is updated only in metadata without a movement record, the two will be out of sync.

---

### 3. **Expected vs. Actual Behavior**

| Scenario | New Mfr | Used Qty | Total Seed Used | Root Cause |
|----------|---------|----------|-----------------|-----------|
| **Expected** | 76 | ~76 | ~104+ (all-time cumulative) | Production and seed deduction on same day |
| **Actual** | 76 | 3 | 28 | Seed movements not recorded for today's production, or recorded on a different date |

**Bilal's Explanation:** "76 produced today... but Used Qty = 3 = Raw materials used today"

This suggests that on 2026-10-15:
- 76 units were **manufactured** (production_output recorded)
- Only 3 units of **seed were deducted** (production_consumption recorded or metadata updated)
- The ratio is 76:3 ≈ 25:1, which is unusual and suggests **partial or incomplete seed recording**

---

### 4. **Where the Data Should Sync**

When manufacturing staff registers production, **two things should happen atomically:**

1. **Production output recorded** as `stock_movements` with:
   - `movement_type = 'production_output'`
   - `direction = 'in'`
   - `quantity = 76`
   - `moved_at = today`

2. **Seed consumption recorded** as `stock_movements` with:
   - `movement_type = 'production_consumption'`
   - `direction = 'out'`
   - `quantity = (seed needed for 76 units)`
   - `moved_at = today`

**Current Issue:** Step 2 is either:
- Not being triggered when production is registered
- Being deferred to a different date
- Being recorded only in metadata without a stock_movement entry

---

### 5. **Evidence from Code**

**File:** `app/Livewire/Admin/Reports/ReportDashboard.php`

- Lines 510–517: New Mfr uses production_output stock_movements with date filter ✅
- Lines 528–557: Total Seed Used reads product metadata (no date filter, all-time) ❌
- Lines 561–574: Used Qty searches for production_consumption movements with date filter ❌
- Lines 475–620: Final row mapping shows `$usedQtyToday` but `$seedForAvailable` pulls from metadata, not movements

**Conclusion:** The report is comparing:
- **Real-time transaction data** (production_output movements) → 76 units
- **Metadata/historical data** (seed_quantity_used field) → 28 all-time units
- **Inconsistent movement data** (production_consumption movements) → 3 today

These sources are not synchronized.

---

## Root Cause

**Primary Issue:** When a production batch is registered, the system creates a `production_output` stock_movement but **does not automatically create a matching `production_consumption` stock_movement** for seed deduction.

**Secondary Issue:** Seed consumption is tracked in **product metadata** (`seed_by_branch`, `seed_quantity_used`, `current_seed_deduction`) instead of being derived from stock_movements. This creates a **dual source of truth** that easily gets out of sync.

---

## Recommendations

### 1. **Audit Production Batch Creation** (SHORT TERM)
- Check `manufacturing_batches` or batch creation logs for 2026-10-15
- Verify if seed deduction was recorded in metadata at the time of production registration
- Determine if the 3 units represents partial consumption or a separate transaction

### 2. **Enforce Atomic Transactions** (MEDIUM TERM)
When production is registered, the system must:
```
BEGIN TRANSACTION
  1. Create production_output stock_movement (76 units in)
  2. Create production_consumption stock_movement (76 * seed_per_unit out)
  3. Update product metadata with seed consumption
COMMIT TRANSACTION
```
Currently, these may be happening at different times or not at all for consumption.

### 3. **Consolidate Seed Tracking** (MEDIUM TO LONG TERM)
Replace dual tracking (metadata + stock_movements) with **single source of truth**:
- **Option A:** Use stock_movements exclusively, derive all reports from them
- **Option B:** Use metadata exclusively, make production_consumption a secondary view

Current hybrid approach invites data inconsistency.

### 4. **Fix Report Queries** (SHORT TERM)
In `getGmManufacturingReport()`, line 528–557:
- Change from reading product metadata to summing production_consumption stock_movements
- Apply same date range to both production_output and production_consumption

Example fix:
```php
$seedMap = DB::table('stock_movements')
    ->where('movement_type', 'production_consumption')
    ->whereDate('moved_at', now()->format('Y-m-d'))  // TODAY
    ->where('direction', 'out')
    ->select('item_id', DB::raw('SUM(quantity) as seed_consumed'))
    ->groupBy('item_id')
    ->get()
    ->keyBy('item_id');
```

### 5. **Add Data Validation**
Add a database check to catch mismatches:
```sql
SELECT 
  p.name,
  SUM(CASE WHEN sm.movement_type = 'production_output' THEN sm.quantity ELSE 0 END) as produced,
  SUM(CASE WHEN sm.movement_type = 'production_consumption' THEN sm.quantity ELSE 0 END) as consumed,
  DATE(sm.moved_at) as date
FROM stock_movements sm
JOIN products p ON sm.item_id = p.id
WHERE DATE(sm.moved_at) = '2026-10-15'
GROUP BY p.id, DATE(sm.moved_at)
HAVING produced > 0 AND consumed = 0;
```

---

## Impact

If 76 units of 'rate oil' were produced but only 3 units of seed were deducted:
- **Raw material inventory is overstated** by ~73 seed units
- **Cost of goods sold is understated** (too little seed cost allocated)
- **Production efficiency metrics are misleading** (seed consumption appears 25x lower than expected)
- **Purchasing forecasts are inaccurate** (seed needs underestimated)

---

## Verification Steps

To confirm this hypothesis:
1. Query `stock_movements` for 'rate oil' on 2026-10-15 grouped by movement_type
2. Check if `production_output` records exist (expected: yes, 76 units)
3. Check if `production_consumption` records exist (expected: no, or only 3 units)
4. Check product metadata for seed_quantity_used and seed_by_branch
5. Interview manufacturing staff: when is seed deduction recorded relative to production?

---

## Files Involved

- **Report Logic:** `app/Livewire/Admin/Reports/ReportDashboard.php`
  - `getGmManufacturingReport()` lines 476–621
  - Lines 528–557 (seed metadata reads)
  - Lines 561–574 (production_consumption stock_movements)
- **Models:** `app/Models/StockMovement.php`
- **View:** `resources/views/livewire/admin/reports/partials/gm-manufacturing.blade.php`
- **Database:** `stock_movements` table, `products.metadata` field

