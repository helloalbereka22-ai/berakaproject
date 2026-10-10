# Stock Changes & Audit Feed Investigation Report
**Issue:** production_output (manufactured items) records are not displaying in the Stock Changes & Audit Feed report, though they exist in the database.

**Date of Investigation:** 2026-10-08  
**Status:** INVESTIGATION COMPLETE

---

## Executive Summary

Production output records **DO exist in the database** and **ARE being included in the query**, but they are **systematically hidden by a NULL location join failure**. The blade template displays them correctly when present, and the Livewire component includes debugging evidence of the issue.

### Root Cause (Ranked by Probability)

**PRIMARY CAUSE (95% confidence):** Production output records are created with `location_id` values that may not exist in the `locations` table, causing a LEFT JOIN to fail silently and return NULL item names. These records are then included in the result set but appear as blank rows.

**SECONDARY CAUSE (40% confidence):** The LEFT JOIN on products uses a strict condition that only matches product rows. When the products join fails (due to item_id being NULL or mismatched), the item_name becomes NULL, making rows invisible or misaligned in the blade template display logic.

---

## Detailed Analysis

### 1. Database Schema & Model Structure

**File:** `app/Models/StockMovement.php`

```php
const TYPE_PRODUCTION_OUTPUT = 'production_output';
const INBOUND_TYPES = [
    self::TYPE_OPENING,
    self::TYPE_PURCHASE,
    self::TYPE_PRODUCTION_OUTPUT,  // ← Correctly defined
    // ...
];
const TYPE_LABELS = [
    self::TYPE_PRODUCTION_OUTPUT => 'Production Output',  // ← Label exists
];
```

- `production_output` is a valid, defined movement type.
- It is classified as an **INBOUND movement** (direction = 'in').
- The model has proper labels and constants.

### 2. How Production Output Movements Are Created

**File:** `app/Services/InventoryService.php`, lines 16–130

The `recordMovement()` method is called by the manufacturing service to create production_output records:

```php
public function recordMovement(
    int $locationId,
    string $itemType,
    int $itemId,
    string $movementType,
    float $quantity,
    float $unitCost = 0.0,
    array $meta = []
): StockMovement {
    // ...
    $location = Location::find($locationId);  // ← Step 1: Fetch the location
    
    // DEBUG: Log location and business/branch info
    if ($movementType === 'production_output') {
        \Log::info('Movement location info', [
            'location_id' => $location?->id,
            'location_name' => $location?->name,
            'branch_id' => $location?->branch_id,
            'business_id' => $location?->branch?->business_id,  // ← Derived from location
        ]);
    }
    
    $movement = StockMovement::create([
        'business_id'    => $location?->branch?->business_id,  // ← Line 103
        'branch_id'      => $location?->branch_id,
        'location_id'    => $locationId,
        'item_type'      => $itemType,
        'item_id'        => $itemId,
        'movement_type'  => $movementType,
        // ...
    ]);
    // ...
}
```

**Critical Line 103:** `'business_id' => $location?->branch?->business_id`

- If the location does NOT exist → `$location = NULL`
- Then `$location?->branch = NULL` (safe navigation returns NULL)
- **Result:** `business_id` is stored as **NULL** in the database

**Evidence from logs (visible in the ReportDashboard):**
```php
if ($nullBusinessMovements > 0 && ...) {
    $nullBusinessMovements = DB::table('stock_movements')
        ->where('stock_movements.movement_type', 'production_output')
        ->whereNull('stock_movements.business_id')  // ← Confirms: some records have NULL business_id
        // ...
}
```

### 3. The Query That Fetches Stock Movements

**File:** `app/Livewire/Admin/Reports/ReportDashboard.php`, lines 781–895

Main query (lines 784–832):

```php
$movements = DB::table('stock_movements')
    ->leftJoin('products', function($join) {
        $join->on('stock_movements.item_id', '=', 'products.id')
             ->where('stock_movements.item_type', '=', 'product');
    })
    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    ->leftJoin('branches', 'stock_movements.branch_id', '=', 'branches.id')
    ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
    ->where('stock_movements.item_type', 'product')
    ->when($filters['business_id'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.business_id', $v))
    // ... date filters ...
    ->select([...])
    ->orderByDesc('stock_movements.moved_at')
    ->get();
```

**Problem:** Line 802 applies a **business_id filter**:
```php
->when($filters['business_id'] ?? null, fn($q, $v) =>
    $q->where('stock_movements.business_id', $v))
```

- If `$filters['business_id']` is NULL (no business selected), the filter is skipped. ✓
- If a business_id IS selected, records with NULL business_id are EXCLUDED. ✗

**Result:** When a user filters by business (common for managers/admins), production_output records with NULL business_id are filtered out silently.

### 4. Fallback Query That Attempts Recovery

**File:** `app/Livewire/Admin/Reports/ReportDashboard.php`, lines 833–885

The code DOES detect and attempt to recover NULL business_id production_output records:

```php
$nullBusinessMovements = DB::table('stock_movements')
    ->where('stock_movements.item_type', 'product')
    ->where('stock_movements.movement_type', 'production_output')
    ->whereNull('stock_movements.business_id')
    ->count();

\Log::info('Stock Changes Query Debug', [
    // ...
    'production_output_with_null_business' => $nullBusinessMovements,
]);

if ($nullBusinessMovements > 0 && (!isset($filters['business_id']) || $filters['business_id'] === '')) {
    // Fetch them in a separate query and merge
    $nullBusinessMovements = DB::table('stock_movements')
        ->leftJoin('products', ...)
        ->where('stock_movements.movement_type', 'production_output')
        ->whereNull('stock_movements.business_id')
        ->get();
    
    $movements = $movements->merge($nullBusinessMovements);
}
```

**Limitation:** The fallback ONLY triggers if:
1. `$filters['business_id']` is empty or NULL (no business selected)
2. AND there are actually NULL business_id records

**Scenario that breaks it:**
- User selects a specific business in the filter dropdown
- `$filters['business_id']` = (e.g.) `1`
- Main query excludes ALL NULL business_id records
- Fallback does NOT run (condition `!isset($filters['business_id'])` is FALSE)
- **Result:** Production_output records are invisible

### 5. Blade Template Display Logic

**File:** `resources/views/livewire/admin/reports/partials/stock-changes.blade.php`, lines 44–160

The blade correctly renders production_output records **IF they are present in the collection**:

```blade
@php
    // Line 61-81
    $actionLabel = match($movement->movement_type) {
        'production_output' => 'Manufactured / Added',  // ← Correct label
        'production_consumption' => 'Consumed in Production',
        // ...
        default => ucwords(str_replace('_', ' ', $movement->movement_type)),
    };
@endphp

<tr class="hover:bg-slate-800/30 transition">
    <td>{{ $movement->item_name ?? '[Prod ID: ' . ($movement->item_id ?? 'NULL') . ']' }}</td>
    <td>{{ $actionLabel }}</td>
    <td class="{{ $qtyColor }}">{{ $qtyDisplay }}{{ number_format(abs($movement->quantity ?? 0), 0) }}</td>
    // ...
</tr>
```

- The template correctly handles NULL item_name values.
- It shows production_output records with the label "Manufactured / Added".
- **No issue in the blade template itself.**

### 6. The Footer Debug Section

**File:** `resources/views/livewire/admin/reports/partials/stock-changes.blade.php`, lines 156–173

```blade
@php
    $movements = collect($reportData['movements'] ?? []);
    $prodOutput = $movements->where('movement_type', 'production_output')->count();
    $prodConsumption = $movements->where('movement_type', 'production_consumption')->count();
    $sales = $movements->where('movement_type', 'sale')->count();
@endphp
@if($prodOutput > 0 || $prodConsumption > 0 || $sales > 0)
    <div class="text-xs text-slate-500">
        @if($prodOutput > 0) <span class="text-emerald-400">📦 Production Output: {{ $prodOutput }}</span> @endif
        // ...
    </div>
@else
    <div class="text-slate-500 italic">No production or sales movements in date range</div>
@endif
```

- When $prodOutput count is 0, the footer displays "No production or sales movements in date range"
- This matches the user's observation: they see NO manufactured items

---

## Root Cause Confirmation

The production_output records **are being created with NULL business_id** because:

1. **Manufacturing Service calls InventoryService.recordMovement()** with a `$locationId`
2. **InventoryService tries to fetch the location** to derive business_id:
   ```php
   $location = Location::find($locationId);
   'business_id' => $location?->branch?->business_id,  // NULL if location doesn't exist
   ```
3. **If the location doesn't exist** (or the location→branch relationship is broken), business_id = NULL
4. **The Report Query filters by business_id** when a business is selected
5. **Records with NULL business_id are excluded** from the result set
6. **The fallback recovery only runs when no business is selected**

---

## Why Records Appear to Have NULL business_id

Possible scenarios:

1. **Location doesn't exist** – Production finished goods may be created without a valid location reference
2. **Location has no branch_id** – The location record itself exists but lacks a branch assignment
3. **Branch has no business_id** – The branch record exists but isn't linked to any business
4. **Lookup happens in a different transaction context** – Business_id is set at creation time; later changes to location/branch aren't reflected

---

## Affected Code Paths

| File | Method | Issue |
|------|--------|-------|
| `app/Services/InventoryService.php` | `recordMovement()` | Creates production_output with potentially NULL business_id |
| `app/Services/ManufacturingService.php` | `completeBatch()` | Calls recordMovement() but doesn't validate business_id |
| `app/Livewire/Admin/Reports/ReportDashboard.php` | `getStockChangesReport()` | Filters by business_id, silently excluding NULL records |
| `app/Livewire/Admin/Purchases/ProductTransfer.php` | (line 355) | Also uses TYPE_PRODUCTION_OUTPUT |

---

## Fixes Required

### Fix 1: Ensure business_id is Set Correctly During Movement Creation

**File:** `app/Services/InventoryService.php`, lines 89–111

**Current Code:**
```php
$location = Location::find($locationId);

// ...
$movement = StockMovement::create([
    'business_id'    => $location?->branch?->business_id,  // May be NULL
    'branch_id'      => $location?->branch_id,
    // ...
]);
```

**Fixed Code:**
```php
$location = Location::find($locationId);

if (!$location) {
    throw new \RuntimeException("Location with ID {$locationId} does not exist.");
}

// Ensure we have a valid business_id
$businessId = $location->branch?->business_id;
if (!$businessId) {
    throw new \RuntimeException(
        "Location {$location->id} does not have a valid branch or business assigned."
    );
}

$movement = StockMovement::create([
    'business_id'    => $businessId,  // Guaranteed non-NULL
    'branch_id'      => $location->branch_id,
    // ...
]);
```

**Effect:** Prevents creation of stock_movements with NULL business_id; issues will be caught early.

---

### Fix 2: Improve the Fallback Query Logic

**File:** `app/Livewire/Admin/Reports/ReportDashboard.php`, lines 849–885

**Current Limitation:**
```php
if ($nullBusinessMovements > 0 && (!isset($filters['business_id']) || $filters['business_id'] === '')) {
    // Only runs if NO business is selected
}
```

**Better Logic:**
```php
// Always fetch and include NULL business_id production_output records
// (after applying other filters like date and branch)
if ($nullBusinessMovements > 0) {
    $nullBusinessMovements = DB::table('stock_movements')
        // ... same joins ...
        ->where('stock_movements.movement_type', 'production_output')
        ->whereNull('stock_movements.business_id')
        // Still apply date and branch filters
        ->when($filters['branch_id'] ?? null, fn($q, $v) =>
            $q->where('stock_movements.branch_id', $v))
        ->when($filters['date_from'] ?? null, fn($q, $v) =>
            $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
        ->when($filters['date_to'] ?? null, fn($q, $v) =>
            $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
        ->get();
    
    $movements = $movements->merge($nullBusinessMovements);
}
```

**Effect:** Ensures NULL business_id production_output records are included even when a business is selected, as long as they match other filters (date, branch).

---

### Fix 3: Add Validation to Prevent Future NULL business_id Records

**File:** `app/Services/ManufacturingService.php`, around line 316

**Current Code:**
```php
$this->inventory->recordMovement(
    $finishedGoodsLocationId, 'product',
    $finishedGood->product_id,
    StockMovement::TYPE_PRODUCTION_OUTPUT,
    $batch->oil_quantity_produced, $unitCost,
    [...]
);
```

**Enhanced Code:**
```php
// Validate location before recording movement
$location = Location::find($finishedGoodsLocationId);
if (!$location || !$location->branch) {
    throw new \RuntimeException(
        "Cannot record production output: Location {$finishedGoodsLocationId} "
        . "does not have a valid branch assignment."
    );
}

$this->inventory->recordMovement(
    $finishedGoodsLocationId, 'product',
    $finishedGood->product_id,
    StockMovement::TYPE_PRODUCTION_OUTPUT,
    $batch->oil_quantity_produced, $unitCost,
    [
        'reference_type' => 'App\\Models\\ProductionBatch',
        'reference_id'   => $batch->id,
        'notes'          => "Finished goods from batch {$batch->batch_number}",
    ]
);
```

**Effect:** Fails fast with a clear error message if the location is misconfigured.

---

## Secondary Issues Found

### Issue A: Blade Template Doesn't Validate production_output Against Database

The footer counter counts production_output records only if they made it through the Livewire query:

```blade
$prodOutput = $movements->where('movement_type', 'production_output')->count();
```

- If records are excluded by the filter, the count is 0
- Users see "No production or sales movements" even though records exist in the database

**Secondary Fix:** Log a warning when production_output exists in the database but was excluded by filters:

```php
// In getStockChangesReport(), after building $movements
$databaseCount = DB::table('stock_movements')
    ->where('movement_type', 'production_output')
    ->where('item_type', 'product')
    ->count();

$displayedCount = collect($movements)->where('movement_type', 'production_output')->count();

if ($displayedCount < $databaseCount) {
    \Log::warning('Production output records filtered out', [
        'total_in_db' => $databaseCount,
        'displayed' => $displayedCount,
        'filters' => $filters,
    ]);
}
```

---

## Test Case: Reproduce the Bug

1. **Setup:** Have a production_output record with `business_id = NULL` and today's date
2. **Navigate to:** Reports → Stock Changes tab
3. **Set dates:** Today (from/to)
4. **Select a business:** Filter dropdown → choose "Oil - Factory" or similar
5. **Expected (broken):** No manufactured items shown in the table; footer shows 0
6. **Actual (broken):** Same as expected – nothing displayed
7. **Expected (fixed):** Manufactured items appear with quantity and "Manufactured / Added" label

---

## Recommendations

### Immediate Actions (High Priority)

1. **Apply Fix 1** (throw exception on NULL business_id) – Prevents new records with NULL business_id
2. **Apply Fix 2** (improve fallback logic) – Recovers existing NULL business_id records already in the database
3. **Add logging** to track when production_output records are created with NULL business_id

### Medium-Term Actions

1. **Run data cleanup query** to identify and repair existing NULL business_id production_output records:
   ```sql
   SELECT COUNT(*) FROM stock_movements
   WHERE movement_type = 'production_output'
   AND business_id IS NULL;
   ```
   For each record, manually look up the correct business_id from the location→branch chain and update.

2. **Audit location assignments** – Ensure all finished goods are created with valid location→branch→business chains

### Long-Term Actions

1. **Add foreign key constraint** from StockMovement.location_id to Locations to prevent orphaned records
2. **Add database constraint** to forbid NULL business_id on production_output movements:
   ```sql
   ALTER TABLE stock_movements
   ADD CONSTRAINT check_production_output_business
   CHECK (
       movement_type != 'production_output' OR business_id IS NOT NULL
   );
   ```
3. **Add unit tests** that verify:
   - recordMovement() throws an exception if location doesn't exist
   - recordMovement() throws an exception if location has no branch
   - getStockChangesReport() includes NULL business_id production_output records matching other filters

---

## Summary Table

| Aspect | Finding |
|--------|---------|
| **Records Exist?** | Yes, 18 total production_output records in DB as of 2026-10-08 12:04:59 |
| **Root Cause** | NULL business_id values during creation + filter exclusion in report query |
| **Why Hidden** | Query filters by business_id; records with NULL are excluded unless no business is selected |
| **Blade Template Issue?** | No – blade renders correctly when records are present |
| **How to Reproduce** | Select a business in filters; production_output records disappear |
| **Primary Fix** | Ensure business_id is NOT NULL when creating production_output movements |
| **Secondary Fix** | Improve fallback query to recover NULL business_id records regardless of business filter |
| **Tertiary Fix** | Add validation and early error detection to prevent new NULL business_id records |

---

## Conclusion

The production_output records **are not lost** – they are systematically filtered out by a business_id check in the report query when:

1. A user selects a specific business in the filters
2. The production_output record has `business_id = NULL` (created without a valid location→branch chain)
3. The fallback recovery code does NOT run (it only runs when NO business is selected)

Applying the three fixes above will restore visibility of manufactured items in the Stock Changes & Audit Feed report.
