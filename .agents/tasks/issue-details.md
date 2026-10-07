# Detailed Issue Analysis: Quantity Mismatch Review

## Issue 1: Metadata still gates Manufacturing visibility
**File:** `app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php`
**Location:** Lines 396–406 (getManufacturedItemsProperty)

### Problem
The "Manufactured Items" list only shows products where `metadata['manufacturing_quantity'] > 0`. When a product is transferred via ProductTransfer.php, the metadata is zeroed (line 265), but the product may still have positive StockMovement balance that can be sold.

```php
// CURRENT CODE (lines 396–406)
->filter(function ($product) {
    $meta = $product->metadata ?? [];
    return isset($meta['manufacturing_quantity']) && $meta['manufacturing_quantity'] > 0;
})
```

### Impact
- Product disappears from "Manufactured Items" table after transfer
- User cannot see it in the manufacturing interface
- But `resolveAvailableQty()` (line 971) WILL see the StockMovement balance
- Creates UI inconsistency: "not manufactured" vs "available to sell"

### Fix
Replace metadata gate with StockMovement balance check:

```php
->filter(function ($product) {
    // Check if product has net positive stock via movements (not just metadata)
    $query = StockMovement::where('item_id', $product->id)
        ->where('item_type', 'product');
    
    if (!empty($this->filterBusinessId)) {
        $query->where('business_id', (int) $this->filterBusinessId);
    }
    
    $stockIn = (float) $query->clone()->where('direction', 'in')
        ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
        ->sum('quantity');
    
    $stockOut = (float) $query->clone()->where('direction', 'out')
        ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
        ->sum('quantity');
    
    return max(0, $stockIn - $stockOut) > 0;
})
```

Also update `getTotalQuantityProperty()` (line 423) to sum StockMovement balance instead of metadata:

```php
// CURRENT (line 424)
return isset($meta['manufacturing_quantity']) ? (float) $meta['manufacturing_quantity'] : 0;

// SHOULD BE
$query = StockMovement::where('item_id', $product->id)
    ->where('item_type', 'product')
    ->when(!empty($this->filterBusinessId), fn($q) => $q->where('business_id', (int)$this->filterBusinessId));

$stockIn = (float) $query->clone()->where('direction', 'in')
    ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
    ->sum('quantity');

$stockOut = (float) $query->clone()->where('direction', 'out')
    ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
    ->sum('quantity');

return max(0, $stockIn - $stockOut);
```

---

## Issue 2: Routes file uses shared constants but can be optimized
**File:** `routes/admin.php`
**Locations:** Lines 200–230 and 290–320

### Current Status
✅ **GOOD:** The code already uses `StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND` and `StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` constants (verified lines 212, 219, 303, 310).

### Verification
Lines 212–221 in routes/admin.php:
```php
$stockIn = (float) \App\Models\StockMovement::whereIn('location_id', $locationIds)
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'in')
    ->whereNotIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)  // ✅ Uses constant
    ->sum('quantity');

$stockOut = (float) \App\Models\StockMovement::whereIn('location_id', $locationIds)
    ->where('item_type', 'product')
    ->where('item_id', $product->id)
    ->where('direction', 'out')
    ->whereIn('movement_type', \App\Models\StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)  // ✅ Uses constant
    ->sum('quantity');
```

**No changes needed** — routes already use the shared constants, so qty will stay in sync.

---

## Issue 3: Movement type exclusions in sync via shared constants
**File:** `app/Models/StockMovement.php`
**Location:** Lines 48–59

### Current Status
✅ **GOOD:** The fix uses shared constants defined in the StockMovement model:

```php
const AVAILABLE_QTY_EXCLUDED_INBOUND = [
    self::TYPE_PRODUCTION_CONSUMPTION,
    self::TYPE_PACKING_CONSUMPTION,
];

const AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND = [
    self::TYPE_SALE,
    self::TYPE_TRANSFER_OUT,
    self::TYPE_ADJUSTMENT_DECREASE,
    self::TYPE_DISTRIBUTION,
];
```

### Where Used
All three locations now use these constants:
- ✅ ProductTransfer.php line 178: `StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND`
- ✅ ProductTransfer.php line 185: `StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND`
- ✅ ManufacturingUserDashboard.php line 994: `StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND`
- ✅ ManufacturingUserDashboard.php line 998: `StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND`
- ✅ routes/admin.php line 212: `StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND`
- ✅ routes/admin.php line 219: `StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND`
- ✅ routes/admin.php line 303: `StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND`
- ✅ routes/admin.php line 310: `StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND`

**No changes needed** — movement type exclusions are automatically kept in sync via the shared constants.

---

## Summary of Actionable Items

| Issue | Status | Action |
|-------|--------|--------|
| Metadata gates manufacturing visibility | ❌ **BLOCKING** | Update `getManufacturedItemsProperty()` and `getTotalQuantityProperty()` in ManufacturingUserDashboard.php to use StockMovement balance instead of metadata |
| Routes file qty mismatch | ✅ **RESOLVED** | Already uses shared constants; qty stays in sync |
| Movement type sync | ✅ **RESOLVED** | Uses shared constants; no sync drift possible |

---

## Testing Checklist

After fixing Issue 1, verify:

1. ✅ Transfer a product from one branch to another via ProductTransfer
2. ✅ Check that product still appears in "Manufactured Items" list (even though metadata is now zero)
3. ✅ Verify total quantity in manufacturing dashboard matches available qty in sell dropdown
4. ✅ Sell the product and confirm qty deducts correctly
5. ✅ Check guest order confirmation endpoints show same qty as Livewire pages
