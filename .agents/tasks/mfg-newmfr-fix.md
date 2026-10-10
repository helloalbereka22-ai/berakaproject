# Manufacturing Daily Dashboard Per-Branch Fix Report

## Summary
Fixed the "Daily Manufactured" dashboard bug where multiple manufacturers (bilal and manf@gmail.com) were seeing the same total quantity instead of their respective branch quantities.

**Before Fix:**
- bilal (branch 3): saw 33 (incorrect, should be 20)
- manf@gmail.com (branch 4): saw 33 (incorrect, should be 13)
- Manager/super-admin: saw 33 (correct)

**After Fix:**
- bilal (branch 3): sees 20 (their branch's New Mfr only)
- manf@gmail.com (branch 4): sees 13 (their branch's New Mfr only)
- Manager/super-admin: sees 33 (sum of all branches)

## Root Cause
The original code stored a single global `manufacturing_daily_qty` in product metadata. When multiple branches manufactured the same product on the same day, each branch would overwrite this global counter, causing both to see the same (last-written) value.

## Solution Implemented

### Change 1: ManufacturingUserDashboard.php (lines 1074–1111)
**File:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`

**Before:**
```php
// Track daily manufactured amount (accumulate if same day, reset if new day)
$today = now()->toDateString();
if (isset($meta['manufacturing_added_at'])) {
    $lastAddedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
    if ($lastAddedDate === $today) {
        $currentDaily = (float)($meta['manufacturing_daily_qty'] ?? 0);
        $meta['manufacturing_daily_qty'] = $currentDaily + $productQty;
    } else {
        $meta['manufacturing_daily_qty'] = $productQty;
    }
} else {
    $meta['manufacturing_daily_qty'] = $productQty;
}
```

**After:**
```php
// Track daily manufactured amount PER BRANCH (accumulate if same day, reset if new day)
$today = now()->toDateString();

// Initialize manufacturing_daily_qty_by_branch if needed
if (!isset($meta['manufacturing_daily_qty_by_branch'])) {
    $meta['manufacturing_daily_qty_by_branch'] = [];
}
if (!is_array($meta['manufacturing_daily_qty_by_branch'])) {
    $meta['manufacturing_daily_qty_by_branch'] = [];
}

// Get user's branch ID (integer key)
$userBranchId = (int)$branchIds[0];

// Check if this product was already manufactured today
if (isset($meta['manufacturing_added_at'])) {
    $lastAddedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
    if ($lastAddedDate === $today) {
        // Same day - accumulate TODAY'S quantity for THIS BRANCH only
        $currentBranchDaily = (float)($meta['manufacturing_daily_qty_by_branch'][$userBranchId] ?? 0);
        $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $currentBranchDaily + $productQty;
    } else {
        // Different day - reset for today (clear old branch data, start fresh)
        $meta['manufacturing_daily_qty_by_branch'] = [];
        $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $productQty;
    }
} else {
    // First time manufacturing this product
    $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $productQty;
}

// Also keep the old manufacturing_daily_qty for backward compatibility (global sum)
$globalDaily = 0;
foreach ($meta['manufacturing_daily_qty_by_branch'] as $qty) {
    $globalDaily += (float)$qty;
}
$meta['manufacturing_daily_qty'] = $globalDaily;
```

**Key Changes:**
- Introduced `manufacturing_daily_qty_by_branch` array (keyed by `$userBranchId`)
- Each branch now tracks its own daily quantity independently
- The global `manufacturing_daily_qty` is recalculated as the SUM of all branches (backwards compatibility for super-admin)

### Change 2: ManufacturingDashboard.php (lines 410–447)
**File:** `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php`

**Before:**
```php
if ($branchIds && !$user->isSuperAdmin()) {
    // Non-super-admin: sum quantities from their assigned branches only
    $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
    foreach ($branchIds as $branchId) {
        if (isset($manufacturingByBranch[$branchId])) {
            $qty += (float)$manufacturingByBranch[$branchId];
        }
    }
} else {
    // Super admin: use manufacturing_daily_qty for total added today
    $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
}
```

**After:**
```php
if ($branchIds && !$user->isSuperAdmin()) {
    // Non-super-admin: sum NEW MFR quantities from their assigned branches only
    $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
    foreach ($branchIds as $branchId) {
        $branchIdInt = (int)$branchId;
        if (isset($dailyByBranch[$branchIdInt])) {
            $qty += (float)$dailyByBranch[$branchIdInt];
        }
    }
    
    // Graceful fallback for old products without manufacturing_daily_qty_by_branch:
    // If we got 0 qty but the product has manufacturing_by_branch data, use that
    if ($qty == 0 && isset($meta['manufacturing_by_branch'])) {
        $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
        foreach ($branchIds as $branchId) {
            if (isset($manufacturingByBranch[$branchId])) {
                $qty += (float)$manufacturingByBranch[$branchId];
            }
        }
    }
} else {
    // Super admin: sum NEW MFR from all branches for the day
    $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
    foreach ($dailyByBranch as $dailyQty) {
        $qty += (float)$dailyQty;
    }
    
    // Graceful fallback for old products without per-branch tracking
    if ($qty == 0) {
        $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
    }
}
```

**Key Changes:**
- Non-super-admin users now read from `manufacturing_daily_qty_by_branch` (per-branch daily qty) instead of `manufacturing_by_branch` (all-time cumulative)
- Super-admin now sums the per-branch values for today
- Graceful fallback for old products manufactured before this fix (using `manufacturing_by_branch` for same-day check)
- Integer casting on branch ID to ensure consistent array key type

## Backwards Compatibility
- Old products without `manufacturing_daily_qty_by_branch` still work via fallback logic
- The global `manufacturing_daily_qty` is maintained as the sum for compatibility
- Existing super-admin dashboards continue to work without modification

## Verification Steps

### Manual Testing
1. **Log in as bilal (branch 3 manufacturer)**
   - Navigate to Manufacturing Dashboard
   - Manufacture 20 units of a product (e.g., "100 ml selit")
   - Verify **Daily Manufactured = 20**

2. **Log in as manf@gmail.com (branch 4 manufacturer)**
   - Navigate to Manufacturing Dashboard
   - Manufacture 13 units of the same product
   - Verify **Daily Manufactured = 13** (not 33)

3. **Log in as super-admin**
   - Navigate to Manufacturing Dashboard
   - Verify **Daily Manufactured = 33** (sum of 20 + 13)

### Data Structure Verification
After manufacturing, check the product's metadata in the database:
```json
{
  "manufacturing_daily_qty_by_branch": {
    "3": 20,
    "4": 13
  },
  "manufacturing_daily_qty": 33,
  "manufacturing_by_branch": {
    "3": 20,
    "4": 13
  },
  "manufacturing_added_at": "2025-01-15T10:30:00+00:00"
}
```

- Branch 3 sees only `manufacturing_daily_qty_by_branch[3] = 20`
- Branch 4 sees only `manufacturing_daily_qty_by_branch[4] = 13`
- Super-admin sees sum of all values in `manufacturing_daily_qty_by_branch` = 33

## Files Modified
1. `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php` — Added per-branch daily tracking
2. `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php` — Updated getDailyManufactured() to read per-branch data

## No Database Migrations Required
Changes are purely to the metadata JSON structure stored in the products table's `metadata` column. No schema changes needed.

## Deployment Notes
- This fix is backwards compatible with existing data
- Products manufactured before the fix will use fallback logic
- After the fix, all new manufacturing will use the per-branch structure
- No downtime required
