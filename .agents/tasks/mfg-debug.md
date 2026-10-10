# Manufacturing Dashboard Bug Investigation

## Bug Report
The Daily Manufactured dashboard at `/admin/manufacturing/dashboard` shows the same total (33.00) for both branches, instead of branch-specific totals.

## Data Findings

### Database State (2026-10-10)
Two products were manufactured today:

**Product 14 (100 ml beherzaf):**
- manufacturing_by_branch: `{"4": 650, "3": 19}`
- manufacturing_quantity: 669 (sum of all branches)
- manufacturing_daily_qty: 13 (total added today across all branches)
- manufacturing_added_at: 2026-10-10T07:08:40+00:00

**Product 24 (100 ml selit):**
- manufacturing_by_branch: `{"3": 51, "4": 17}`
- manufacturing_quantity: 68 (sum of all branches)
- manufacturing_daily_qty: 20 (total added today across all branches)
- manufacturing_added_at: 2026-10-10T06:31:41+00:00

### Branch Information
- Branch 3: "Oil Manufacturing"
- Branch 4: "Bazar"

### Calculation Per Branch
- **Branch 3 (Oil Manufacturing)**: 19 + 51 = 70 units manufactured today
- **Branch 4 (Bazar)**: 650 + 17 = 667 units manufactured today
- **Dashboard shows**: 33.00 (which is 13 + 20, the sum of manufacturing_daily_qty values)

## Root Cause Analysis

### The Bug Location
File: `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Manufacturing\ManufacturingDashboard.php`
Method: `getDailyManufactured()` at lines 410-415

```php
return $items->map(function ($product) {
    $meta = $product->metadata ?? [];
    
    // Use manufacturing_daily_qty if available, otherwise use manufacturing_quantity
    $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
```

### Why It's Wrong
1. The method returns an array of items with a `quantity` field set to `manufacturing_daily_qty`
2. The blade template then sums these: `$this->dailyManufactured->sum('quantity')`
3. This sums the TOTAL daily quantities (13 + 20 = 33), NOT the branch-specific quantities
4. Both Branch 3 and Branch 4 users see the same 33.00 because they both see the same summed daily totals

### The Correct Logic
- For each product, we need to extract ONLY the branch-specific quantity from `manufacturing_by_branch`
- The blade template already filters which products to show per branch (lines 378-395), but the `quantity` field in the returned map is incorrect
- The method DOES check branch ownership correctly (line 382-395), but then assigns the wrong quantity value

## Required Fix

In `getDailyManufactured()` method, after line 395 (the map function), we need to:

1. Pass the user's branch information to the map function
2. For each product, extract the quantity from `manufacturing_by_branch[$userBranchId]` instead of using `manufacturing_daily_qty`
3. This ensures each branch sees only their own manufactured quantity

### Code Change Required

**File:** `c:\xammpp82\htdocs\albarekaaaaaa\app\Livewire\Admin\Manufacturing\ManufacturingDashboard.php`

**Lines 406-420 (after the filter) should become:**

```php
->values();

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
        // Super admin: use manufacturing_daily_qty or total from all branches
        $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
    }
    
    return [
        'product_id'       => $product->id,
        'item_name'        => $product->name,
        'category_name'    => $product->category?->name ?? '—',
        'quantity'         => $qty,
        'unit'             => $product->unit ?? 'pcs',
        'selling_price'    => (float) $product->selling_price,
        'seed_name'        => $meta['seed_name'] ?? '—',
        'seed_qty_used'    => (float)($meta['seed_quantity_used'] ?? 0),
        'seed_unit'        => $meta['seed_unit'] ?? 'kg',
        'created_at'       => $meta['manufacturing_added_at'] ?? $product->updated_at,
    ];
});
```

## Expected Result After Fix
- **Branch 3 users** will see: 19 + 51 = 70 units manufactured today
- **Branch 4 users** will see: 650 + 17 = 667 units manufactured today
- **Super admin** will see: 33.00 (the daily total) or a selection option
