# Implementation Plan: Fix Branch-Isolated Manufacturing

## Overview
The manufacturing system currently shares global counters between branches, causing data leakage and incorrect inventory tracking. The root cause is the shared `manufacturing_quantity` field in product metadata. This plan isolates manufacturing by branch so each branch only sees and manages their own manufactured items.

## Design Decisions

### 1. Metadata Structure (Key Decision)
**Decision:** Replace single shared counter with branch-isolated tracking.

**Rationale:** The existing `manufacturing_by_branch[branchId]` structure is correct but is shadowed by the shared `manufacturing_quantity` field used in product filtering. By removing reliance on `manufacturing_quantity` for branch filtering and using only the branch-specific counter, we achieve isolation while maintaining backward compatibility through a sum of branch counters.

**Structure:**
```php
metadata = [
    'manufacturing_by_branch' => [
        (int)branchId => (float)qty,  // ONE counter per branch
        ...
    ],
    'manufacturing_quantity' => sum($manufacturing_by_branch),  // Computed, not used for filtering
    'manufacturing_daily_qty' => (float),  // Daily total (all branches combined)
    'manufacturing_added_at' => ISO8601,   // Timestamp
    'seed_quantity_used' => (float),       // Display only
    'seed_name' => string,                 // Display only
    'seed_unit' => string,                 // Display only
]
```

### 2. JSON Key Type Consistency
**Decision:** Always use integer keys when storing `manufacturing_by_branch[branchId]`.

**Rationale:** PHP JSON decode coerces numeric strings to integers; storing as ints ensures consistency and prevents lookup failures from type mismatches (int vs string branch IDs).

### 3. TransferredProducts Date Filter Issue
**Decision:** Date filters use ISO 8601 timestamps but form sends dates as Y-m-d. Need defensive parsing.

**Rationale:** The `dateTo` filter ends at midnight (23:59:59), which works correctly in the query. The page shows 0 results because there are likely NO actual StockMovement records with TYPE_TRANSFER_IN, TYPE_TRANSFER_OUT, or TYPE_DISTRIBUTION in the date range for that user's branch. The fix involves correctly filtering and investigating what actual data exists.

---

## Implementation Steps

### Step 1: Fix `manufacturediItems()` – Branch-aware product filtering
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

In `manufacturediItems()` computed property:
- Change the inclusion logic from checking only `manufacturing_quantity > 0` to checking `manufacturing_by_branch[userBranchId] > 0` (with int key).
- For super admins: keep showing all products with `manufacturing_quantity > 0` (unchanged).
- For non-super-admin users: check EACH assigned branch ID in `manufacturing_by_branch` (as int key).
- Skip products that have no `manufacturing_by_branch` structure OR have zero quantity for the user's branch.
- **Status:** Critical path; blocks other changes.
- **Verify:** Run the Manufacturer Products page; verify the user sees ONLY their branch's manufactured items, not global items.

### Step 2: Fix `addSalesItem()` – Branch isolation on add
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

In `addSalesItem()` method, within the transaction:
- **Keep existing:** Update `manufacturing_by_branch[(int)branchId]` by adding $productQty.
- **Change:** DO NOT update `manufacturing_quantity` directly; instead, recalculate it at the end as `sum($manufacturing_by_branch)`.
- Ensures new products are added to the correct branch only.
- For daily tracking: continue accumulating `manufacturing_daily_qty` (this is currently shared, but acceptable for daily summary).
- **Verify:** Manufacturing a product in one branch does NOT appear in another branch's list. Verify the quantity sum is correct.

### Step 3: Fix `saveSalesItem()` – Edit respects branch isolation
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

In `saveSalesItem()` method (edit modal):
- **Key change:** Update ONLY `manufacturing_by_branch[(int)branchId]`, not `manufacturing_quantity`.
- Recalculate `manufacturing_quantity` as `sum($manufacturing_by_branch)` after setting the branch quantity.
- When a product has no manufacturing history yet, initialize `manufacturing_by_branch` structure.
- **Verify:** Editing a manufactured item quantity updates only that branch's counter. Verify no cross-branch impact.

### Step 4: Fix `deleteSalesItem()` – Delete respects branch isolation
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

In `deleteSalesItem()` method (delete confirmation):
- **Current bug:** Sets `manufacturing_quantity = 0` globally (deletes from ALL branches).
- **Fix:** Zero out ONLY `manufacturing_by_branch[(int)branchId]`, then recalculate `manufacturing_quantity` as the sum of remaining branch quantities.
- If after deletion all branches have qty 0, then `manufacturing_quantity` becomes 0.
- **Verify:** Deleting a product from one branch does NOT delete it from other branches. If Branch A has 10 and Branch B has 5, deleting 10 from A leaves B with 5 visible.

### Step 5: Fix `getTotalQuantityProperty()` – Display correct branch total
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

In `getTotalQuantityProperty()` method:
- **Ensure:** For non-super-admin users, sum ONLY this branch's quantities from `manufacturing_by_branch[(int)branchId]` across all assigned branches.
- **Fallback:** For products without `manufacturing_by_branch` (legacy), use `manufacturing_quantity` as-is (already correct in code, verify).
- **Verify:** The "Total Quantity" summary in the dashboard matches the sum of visible items.

### Step 6: Fix `getDailyManufactured()` – Verify branch-aware filtering
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

**Note:** This method does not exist in the current code but is mentioned in the root cause. If it exists:
- Verify it checks `manufacturing_by_branch` and never falls back to shared counter for branch filtering.
- **No changes needed if:** The method correctly checks branch-specific keys only.

### Step 7: Fix TransferredProducts component – Debug date filter and empty results
**Files:** `app/Livewire/Admin/Manufacturing/TransferredProducts.php`

**Root cause:** The page queries StockMovement records with movement types (TYPE_TRANSFER_IN, TYPE_TRANSFER_OUT, TYPE_DISTRIBUTION). Current code may be:
1. Querying correctly but data doesn't exist for the date range.
2. Using the wrong date format or timezone.

**Changes:**
- Verify date parsing: `dateFrom` and `dateTo` are in Y-m-d format; ensure they are cast correctly when building the query.
- Confirm timezone consistency: if `moved_at` is stored in UTC, ensure date filters account for user timezone.
- Verify location filtering: ensure `branchLocations` returns the correct locations for the user's branches.
- **Add diagnostics (temporary):** Log the query and result count to understand why 0 items are returned.

**Verify:** The Transferred Products page shows data within the date range. If still 0, it means no transfers exist in the database (expected if none have been created via UI or API).

### Step 8: Verify `deductRawMaterial()` – Already branch-isolated
**Files:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

- This method already uses `$this->filterBranchId` to deduct materials per-branch.
- **Verify:** No changes needed; confirm it respects branch boundaries.

---

## Verification Strategy

### Per-Step Verification
1. **After Step 1:** Log in as two different branch users. Manufacture an item in Branch A. Verify Branch B does NOT see it in their Manufacturer Products list. Verify Branch A sees their own items.

2. **After Step 2:** Manufacture 5 units in Branch A, 3 in Branch B of the same product. Verify the product appears in BOTH branches with correct quantities (5 and 3 respectively).

3. **After Step 3:** Edit the quantity in Branch A's list to 10. Verify Branch B's quantity remains 3. Verify the "Total Quantity" display reflects 10+3=13 (if super admin sees summary) or just 10 (if Branch A user views).

4. **After Step 4:** Delete 5 units from Branch A (leaving it with 5). Verify Branch B still shows 3. Delete all of Branch B (0 remaining). Verify product disappears from Branch B's list but remains in Branch A.

5. **After Step 5:** Dashboard summary should show only the current user's branch total, not global total.

6. **After Step 7:** Navigate to Transferred Products page, change date range, verify it loads data or correctly shows 0 if no transfers exist.

### Integration Test (End-to-End)
1. Create User (bilal, Branch A: "Bazar")
2. Create User (manufacturer, Branch B: "Manufacturer")
3. bilal manufactures: 100ml Tukur Azmud Oil (qty 20)
4. Verify in Bazar branch: shows 20 qty
5. Verify in Manufacturer branch: shows 0 qty (not visible)
6. manufacturer manufactures: 100ml Tukur Azmud Oil (qty 10)
7. Verify in Bazar branch: still shows 20 qty
8. Verify in Manufacturer branch: shows 10 qty
9. bilal edits their quantity to 25
10. Verify Manufacturer branch: still shows 10 qty
11. bilal deletes all (qty → 0)
12. Verify Bazar branch: product removed from list
13. Verify Manufacturer branch: still shows 10 qty

---

## Files to Modify

1. `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`
   - `manufacturediItems()` – Computed property
   - `addSalesItem()` – Action method
   - `saveSalesItem()` – Edit action
   - `deleteSalesItem()` – Delete action
   - `getTotalQuantityProperty()` – Verify/fix
   - `deductRawMaterial()` – Verify (likely no changes)

2. `app/Livewire/Admin/Manufacturing/TransferredProducts.php`
   - `transferredProducts()` – Computed property
   - Date filter logic (verify)
   - Timezone/date format handling

---

## Testing Checklist

- [ ] Manufacture in Branch A; Branch B user does NOT see it
- [ ] Manufacture in both branches; each sees only their own qty
- [ ] Edit quantity in Branch A; Branch B qty unchanged
- [ ] Delete all from Branch A; product disappears from A's list but remains in B's list
- [ ] Dashboard Total Quantity = sum of current branch's items
- [ ] Transferred Products page loads data or correctly shows empty
- [ ] After date range changes, data updates dynamically
- [ ] Super admin sees global summary (all branches combined)
- [ ] Non-admin users see only their branch's summary

---

## Rollback Plan

If issues arise:
1. Revert the three methods in ManufacturingUserDashboard to only use `manufacturing_quantity` (ignoring `manufacturing_by_branch` structure).
2. This rolls back to the current broken state but does not introduce new data corruption.
3. No database migrations required; all changes are logical/algorithmic in Livewire code.

---

## Notes

- **No database schema changes required.** All fixes are in PHP/Livewire logic.
- **Backward compatibility:** Old products without `manufacturing_by_branch` will fall back to `manufacturing_quantity` (already handled).
- **JSON key type:** Always cast branch IDs to `(int)` when accessing `manufacturing_by_branch`.
- **User education:** Inform users that each branch now has isolated manufacturing. Items added in one branch do not appear in others.
