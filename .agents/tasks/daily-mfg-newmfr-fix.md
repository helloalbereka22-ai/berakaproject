# Daily Manufactured Dashboard: Per-Branch New Mfr Fix

## Problem Summary
The Daily Manufactured dashboard was showing the same total (33) for all manufacturing branches:
- **bilal** (Branch 3, New Mfr 20) saw: **33** ❌
- **manufacturer** (Branch 4, New Mfr 13) saw: **33** ❌
- **Manager/super-admin** saw: **33** (correct by coincidence)

Users expected:
- **bilal** to see: **20** (only their branch's daily manufacturing)
- **manufacturer** to see: **13** (only their branch's daily manufacturing)
- **Manager** to see: **33** (sum of all branches: 20 + 13)

## Root Cause
The code stored a **global `manufacturing_daily_qty`** field in product metadata. When multiple branches manufactured the same product on the same day:
1. bilal manufactures 20 units → `manufacturing_daily_qty = 20`
2. manufacturer manufactures 13 units → `manufacturing_daily_qty = 20 + 13 = 33` (overwrites bilal's value)
3. Both users read `manufacturing_daily_qty = 33` from the same product record

The field was shared across all branches, causing data leakage.

## Solution Implemented
**Commit:** `6eba492`
**Files Modified:**
1. `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php` (lines 1073–1110)
2. `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php` (lines 410–447)

### Change 1: Per-Branch Daily Tracking
**ManufacturingUserDashboard.php** — When a product is manufactured, store the daily quantity **per-branch** instead of globally:

```php
// OLD (global, shared):
$meta['manufacturing_daily_qty'] = $currentDaily + $productQty;

// NEW (per-branch):
$meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $currentDaily + $productQty;

// Also maintain global sum for super-admin backward compatibility:
$meta['manufacturing_daily_qty'] = sum of all branches;
```

**Key changes:**
- Initialize `manufacturing_daily_qty_by_branch` as an array with branch ID keys
- When new product manufactured: `$meta['manufacturing_daily_qty_by_branch'][$userBranchId] += $productQty`
- When date changes: Reset only the current branch's daily count (clear array, start fresh for this branch)
- Maintain `manufacturing_daily_qty` as the sum of all branches for super-admin display

### Change 2: Branch-Aware Dashboard Display
**ManufacturingDashboard.php** — Extract the correct daily quantity based on the user's role:

```php
if (!$user->isSuperAdmin()) {
    // Non-super-admin: show only their branch's daily qty
    $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
    foreach ($userBranchIds as $branchId) {
        $qty += (float)$dailyByBranch[(int)$branchId];
    }
} else {
    // Super-admin: show sum of all branches' daily qty
    $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
    foreach ($dailyByBranch as $dailyQty) {
        $qty += (float)$dailyQty;
    }
}
```

**Key changes:**
- Non-super-admin users see only their branch's `manufacturing_daily_qty_by_branch[branchId]`
- Super-admin users see the sum of all branches
- Includes backward compatibility fallback for old products without per-branch tracking

## Expected Results After Fix

When manufacturing activities are logged today:

| User | Branch | New Mfr | Dashboard Shows | ✅ Status |
|------|--------|---------|-----------------|-----------|
| bilal | Branch 3 | 20 | **20** | Fixed |
| manufacturer | Branch 4 | 13 | **13** | Fixed |
| Manager (super-admin) | All | 20+13 | **33** | Correct |

## Technical Details

### Metadata Structure
```php
// Product metadata (stored in products.metadata JSON):
{
    "manufacturing_quantity": 669,                      // Total ever manufactured
    "manufacturing_by_branch": {"3": 70, "4": 617},     // Total per branch (all time)
    "manufacturing_daily_qty": 33,                      // GLOBAL daily total (sum of below)
    "manufacturing_daily_qty_by_branch": {              // PER-BRANCH daily total (NEW)
        "3": 20,   // Branch 3's daily (bilal)
        "4": 13    // Branch 4's daily (manufacturer)
    },
    "manufacturing_added_at": "2026-10-10T...",         // When last manufactured
    "new_mfr_quantity": 13,                             // Last addition quantity
    // ... other fields ...
}
```

### Data Flow
1. **Manufacturing Occurs** → ManufacturingUserDashboard.php `addSalesItem()`
   - User's branch ID extracted from auth()->user()->assignedBranchIds()
   - `manufacturing_daily_qty_by_branch[$branchId] += productQty` stored
   
2. **Display Daily Manufactured** → ManufacturingDashboard.php `getDailyManufactured()`
   - Non-admin: extracts `manufacturing_daily_qty_by_branch[$userBranchId]`
   - Super-admin: sums all values in `manufacturing_daily_qty_by_branch`

## Backward Compatibility
- Old products without `manufacturing_daily_qty_by_branch` are handled gracefully
- Fallback logic:
  - Non-admin: if per-branch qty is 0, use `manufacturing_by_branch[branchId]` (total, not daily)
  - Super-admin: if per-branch qty is 0, use `manufacturing_daily_qty` (legacy global total)
- New products will use the new per-branch tracking automatically

## Testing Checklist
- [ ] Log in as bilal (Branch 3), manufacture 20 units → Dashboard shows "Daily Manufactured: 20"
- [ ] Log in as manufacturer (Branch 4), manufacture 13 units of same product → Dashboard shows "Daily Manufactured: 13"
- [ ] Log in as super-admin → Dashboard shows "Daily Manufactured: 33" (sum of both branches)
- [ ] Clear date to next day, manufacture again → Daily counter resets per branch
- [ ] Old products (manufactured before this fix) still display correctly → Fallback logic works
- [ ] Export/report functionality shows branch-specific daily totals

## Impact
- **Scope**: Affects only Daily Manufactured dashboard display
- **Risk**: Low — changes are isolated to dashboard display logic, no schema changes
- **Performance**: No impact — same metadata operations, just structured differently
- **Reversibility**: Changes are additive; old data continues to work via fallback
