# Manufacturing Daily Dashboard Bug Fix Plan

## Problem Summary
Two manufacturers (bilal, branch 3 and manf@gmail.com, branch 4) both see **Daily Manufactured = 33** on their dashboards. 
- **bilal (branch 3)** should see **20** (his branch's New Mfr)
- **manf@gmail.com (branch 4)** should see **13** (his branch's New Mfr)  
- **Manager/super-admin** should see **33** (sum of all branches)

**Root Cause:** The code writes a GLOBAL `manufacturing_daily_qty` into product metadata. Both branches write to the same field on the same product record, so the last writer wins. The reader in `ManufacturingDashboard.php::getDailyManufactured()` uses `manufacturing_daily_qty` for both super-admin and non-admin cases, causing the wrong value to be displayed.

## Solution Overview
Replace the global `manufacturing_daily_qty` with a per-branch structure:
1. Add `manufacturing_daily_qty_by_branch` array to metadata (key = branch_id, value = daily qty for that branch)
2. Keep `manufacturing_daily_qty` updated as the **SUM** of all branches in `manufacturing_daily_qty_by_branch` (backwards compatibility for super-admin)
3. Update `getDailyManufactured()` to:
   - For non-super-admin: extract qty from `manufacturing_daily_qty_by_branch[user_branch_id]`
   - For super-admin: use `manufacturing_daily_qty` (the sum)

---

## Implementation Changes

### 1. File: `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`
**Location:** Around lines 1074–1093 (the "Track daily manufactured amount" block)

**BEFORE:**
```php
// Track daily manufactured amount (accumulate if same day, reset if new day)
$today = now()->toDateString();

// Check if this product was already manufactured today
if (isset($meta['manufacturing_added_at'])) {
    $lastAddedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
    if ($lastAddedDate === $today) {
        // Same day - accumulate today's total
        $currentDaily = (float)($meta['manufacturing_daily_qty'] ?? 0);
        $meta['manufacturing_daily_qty'] = $currentDaily + $productQty;
    } else {
        // Different day - reset for today
        $meta['manufacturing_daily_qty'] = $productQty;
    }
} else {
    // First time manufacturing this product
    $meta['manufacturing_daily_qty'] = $productQty;
}
```

**AFTER:**
```php
// Track daily manufactured amount per branch (accumulate if same day, reset if new day)
$today = now()->toDateString();
$userBranchId = (int) $userBranchId; // Ensure numeric key for array

// Initialize per-branch daily tracking if not exists
if (!isset($meta['manufacturing_daily_qty_by_branch'])) {
    $meta['manufacturing_daily_qty_by_branch'] = [];
}

// Ensure manufacturing_daily_qty_by_branch is an array
if (!is_array($meta['manufacturing_daily_qty_by_branch'])) {
    $meta['manufacturing_daily_qty_by_branch'] = [];
}

// Check if this product was already manufactured today
if (isset($meta['manufacturing_added_at'])) {
    $lastAddedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
    if ($lastAddedDate === $today) {
        // Same day - accumulate today's total for THIS BRANCH only
        $currentDaily = isset($meta['manufacturing_daily_qty_by_branch'][$userBranchId])
            ? (float)$meta['manufacturing_daily_qty_by_branch'][$userBranchId]
            : 0;
        $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $currentDaily + $productQty;
    } else {
        // Different day - reset for today, THIS BRANCH only
        $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $productQty;
    }
} else {
    // First time manufacturing this product, THIS BRANCH only
    $meta['manufacturing_daily_qty_by_branch'][$userBranchId] = $productQty;
}

// Update the global manufacturing_daily_qty as the SUM of all branches (for super-admin backwards compat)
$dailyTotal = 0;
foreach ($meta['manufacturing_daily_qty_by_branch'] as $qty_value) {
    if (is_numeric($qty_value)) {
        $dailyTotal += (float)$qty_value;
    }
}
$meta['manufacturing_daily_qty'] = $dailyTotal;
```

**Files to modify:** 
- `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php` (lines 1074–1093)

**Verify:** No syntax errors; class still loads without issues

---

### 2. File: `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php`
**Location:** In the `getDailyManufactured()` method, around lines 410–430 (the quantity extraction loop)

**BEFORE:**
```php
return $items->map(function ($product) use ($branchIds, $user) {
    $meta = $product->metadata ?? [];
    
    // Extract quantity based on user's branch(es)
    $qty = 0;
    
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
    
    return [
        'product_id'       => $product->id,
        // ... rest of fields ...
    ];
});
```

**AFTER:**
```php
return $items->map(function ($product) use ($branchIds, $user) {
    $meta = $product->metadata ?? [];
    
    // Extract quantity based on user's branch(es)
    $qty = 0;
    
    if ($branchIds && !$user->isSuperAdmin()) {
        // Non-super-admin: sum quantities from their assigned branches only using manufacturing_daily_qty_by_branch
        $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
        foreach ($branchIds as $branchId) {
            $branchIdInt = (int)$branchId;
            if (isset($dailyByBranch[$branchIdInt]) && (float)$dailyByBranch[$branchIdInt] > 0) {
                $qty += (float)$dailyByBranch[$branchIdInt];
            }
        }
        
        // Graceful fallback for old products without manufacturing_daily_qty_by_branch:
        // If we got 0 qty but the product has manufacturing_by_branch data for today, use that
        if ($qty == 0 && isset($meta['manufacturing_by_branch'])) {
            $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
            foreach ($branchIds as $branchId) {
                if (isset($manufacturingByBranch[$branchId])) {
                    $qty += (float)$manufacturingByBranch[$branchId];
                }
            }
        }
    } else {
        // Super admin: use manufacturing_daily_qty for total added today (sum of all branches)
        $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
    }
    
    return [
        'product_id'       => $product->id,
        // ... rest of fields ...
    ];
});
```

**Files to modify:** 
- `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php` (lines 410–430, in the `getDailyManufactured()` method's return map closure)

**Verify:** Run `php artisan tinker` and manually query a product with today's manufacturing to confirm `manufacturing_daily_qty_by_branch` structure and that each branch sees the correct daily total

---

## Testing & Verification

### Unit/Integration Tests
- **Branch 3 manufacturer (bilal):** Log in as bilal, manufacture 20 units of product X, navigate to manufacturing dashboard, verify **Daily Manufactured = 20**
- **Branch 4 manufacturer (manf@gmail.com):** Log in as manf, manufacture 13 units of product X (same product), navigate to manufacturing dashboard, verify **Daily Manufactured = 13**
- **Super-admin:** Log in as admin, navigate to manufacturing dashboard, verify **Daily Manufactured = 33** (sum of 20 + 13)

### Backwards Compatibility Check
- **Old products without `manufacturing_daily_qty_by_branch`:** Verify fallback to `manufacturing_by_branch` still works for display

### Regression Tests
Run the full test suite covering manufacturing:
```bash
php artisan test tests/Feature/Manufacturing --filter=dashboard
```

---

## Implementation Notes
- **No database migration needed:** Changes are only to the metadata JSON structure stored in the `products` table
- **Backwards compatible:** Keeps `manufacturing_daily_qty` as the sum for super-admin use; fallback logic ensures old products still display correctly
- **Branch ID as integer key:** Use `(int)$branchId` consistently to avoid array key type mismatches (int vs string)
- **Idempotent:** Running the fix multiple times produces the same result (safe for re-deployment)

---

## Files to Modify (Summary)
1. `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php` — Replace global `manufacturing_daily_qty` block with per-branch `manufacturing_daily_qty_by_branch` structure
2. `app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php` — Update `getDailyManufactured()` to read from per-branch structure for non-admin users

