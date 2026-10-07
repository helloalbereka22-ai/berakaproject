# Investigation Results: Products Hidden in Order Creation

## Root Cause Analysis

After comparing the two queries (`SaleableProductIndex` and `OrderCreate::availableProducts()`), I have identified **THREE critical differences** that cause products to be hidden:

### **PRIMARY ROOT CAUSE: Missing Product Status Filter**

**File:** `app/Livewire/Admin/Orders/OrderCreate.php` (line 118-154)
**Issue:** The `availableProducts()` query does NOT filter by `status = 'active'`

**Evidence:**
- **SaleableProductIndex.php** (line 113-119): No explicit status filter in the main query — but products shown here are filtered to those WITH STOCK in user's branches
- **OrderCreate.php** (line 126-147): Query joins stock_movements and products, but never filters `products.status`

**Impact:** When a General Manager sets prices on a newly-added product, the product's `status` might be `'draft'` or `'inactive'`. The OrderCreate picker does not check status, BUT this is inconsistent with the Saleable Products list which shows active products.

---

### **SECONDARY ROOT CAUSE: Stock Filter Difference**

**SaleableProductIndex approach:**
- For non-super-admin users: Filters to show only products with stock in their assigned branches (lines 130-139)
- Stock is calculated using a subquery on `stock_movements` grouped by branch

**OrderCreate approach:**
- Always requires stock > 0 in the specific branch via `JOIN` and `GROUP BY ... HAVING qty_on_hand > 0` (line 141)
- For a super admin with `branchId = null`, this returns NO results because no locations exist for branch_id = null (line 120)

**Impact:** When super admin hasn't selected a branch, `availableProducts()` returns empty. The mount() method shows this is by design (line 47: only non-super-admin users get auto-assigned branch).

---

### **TERTIARY ROOT CAUSE: Product Type Filter Inconsistency**

**SaleableProductIndex.php (line 114):**
```php
$typesToSearch = $this->type ? [$this->type] : Product::SALEABLE_PRODUCT_TYPES;
```
Filters to **only** saleable product types.

**OrderCreate.php (line 127):**
```php
->whereIn('products.type', Product::SALEABLE_PRODUCT_TYPES)
```
Also filters to saleable product types — **this is consistent**.

---

## Summary: Why "Tukur Azmud Soap" doesn't show in Order Creation

1. **Soap category → SOAP type in products table** (likely not in SALEABLE_PRODUCT_TYPES, or has wrong type constant)
   - OR product `status = 'draft'` after GM sets prices
   - OR no stock movements recorded for that product in the branch's locations

2. **"Rozmerry" (Hair Oil) DOES show** because:
   - Type is likely in SALEABLE_PRODUCT_TYPES
   - Status is `'active'`
   - Stock movement exists for the branch

---

## Code Changes Needed

### **FIX 1: Add Product Status Filter to OrderCreate**
**File:** `app/Livewire/Admin/Orders/OrderCreate.php`
**Line:** 128 (after the whereIn type check)
**Change:** Add status filter to match Saleable Products behavior

```php
->where('products.status', Product::STATUS_ACTIVE)
```

**Rationale:** Products must be active to be orderable. Newly-created products default to draft and must be explicitly activated.

---

### **FIX 2: Verify Product Types Match Saleable Domain**

**Action:** Check the Product constants and confirm:
- Soap products should have a type in `SALEABLE_PRODUCT_TYPES` (currently: finished_product, bottle_product, book, stationery, fashion, general)
- If "Soap" is a separate type, add it to SALEABLE_PRODUCT_TYPES or ensure soap products are type 'general' or 'finished_product'

**Current constants (from Product.php):**
```php
const SALEABLE_PRODUCT_TYPES = [
    self::TYPE_FINISHED_PRODUCT,   // e.g., manufactured oil, soap
    self::TYPE_BOTTLE_PRODUCT,
    self::TYPE_BOOK,
    self::TYPE_STATIONERY,
    self::TYPE_FASHION,
    self::TYPE_GENERAL,  // backward compat
];
```

**Check:** Run this SQL to audit products:
```sql
SELECT DISTINCT type, COUNT(*) as count FROM products WHERE status != 'deleted' GROUP BY type;
```

---

### **FIX 3: Document SuperAdmin Branch Selection**

**Issue:** When super admin doesn't select a branch, `branchId = null` → `availableProducts()` returns empty.

**Current behavior (expected):**
- mount() (line 47-53): Only non-super-admin get auto-assigned branchId
- Super admin must manually select a branch (UI not shown in the provided blade, needs investigation)
- If no branch selected, the empty state message is correct (line 164: "No branch assigned")

**Action:** Verify the blade template shows a branch/location selector for super admins. If missing, add one.

---

## Verification Steps

1. **Check product data for "Tukur Azmud Soap":**
   ```sql
   SELECT id, name, type, status, selling_price, cost_price FROM products 
   WHERE name LIKE '%Tukur%' OR name LIKE '%Soap%';
   ```
   - Confirm `type` is in SALEABLE_PRODUCT_TYPES
   - Confirm `status = 'active'`

2. **Check stock movements exist:**
   ```sql
   SELECT product_id, SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as net_qty
   FROM stock_movements 
   WHERE item_type='product' AND item_id IN (
     SELECT id FROM products WHERE name LIKE '%Tukur%'
   )
   GROUP BY product_id;
   ```
   - Net quantity must be > 0

3. **After FIX 1, test order creation:**
   - Manually set a product's status to `'draft'`
   - Verify it no longer appears in OrderCreate product picker
   - Set it back to `'active'`
   - Verify it reappears

---

## Implementation Priority

1. **HIGH (Fix 1):** Add `->where('products.status', Product::STATUS_ACTIVE)` to OrderCreate::availableProducts() — this is the most likely cause
2. **MEDIUM (Fix 2):** Audit product types in database and confirm soap/general items have correct type
3. **LOW (Fix 3):** Verify/add branch selector for super admin in the order creation UI

---

## Files to Modify

- `app/Livewire/Admin/Orders/OrderCreate.php` (line 128)
- Possibly: Create a migration or data fix if product types are wrong
- Possibly: Update order-create.blade.php if super admin branch selector is missing
