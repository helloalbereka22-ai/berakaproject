# Quantity Mismatch Fix: Manufacturing and Transfer Stock Alignment

The fix migrates both Manufacturing Products and Transfer Products pages to use a unified StockMovement-based calculation for available quantities, eliminating the double-counting that occurred between metadata and FinishedGood records.

**Watch for**: (1) Consistency between `resolveAvailableQty()` and `getSourceBalance()` — both methods must apply identical filters. (2) Products with stock movements but zero metadata may now appear in transfer dropdowns when previously hidden. (3) Dropdowns may show products with movements even if their metadata field is empty, creating a potential visibility mismatch.

**Verdict**: NEEDS_CHANGES

---

## High-level view

Both modules now use StockMovement-only calculations with identical filters: inbound excludes production_consumption and packing_consumption; outbound counts only sale, transfer_out, adjustment_decrease, and distribution. ProductTransfer's `getSourceBalance()` and ManufacturingUserDashboard's `resolveAvailableQty()` apply these same filter constants (confirmed, lines 175–182 and 1006–1012 respectively).

ProductTransfer's `categories()` and `products()` populate dropdowns based on net StockMovement balance (line 207–214: `having('bal', '>', 0)`), no longer checking metadata. When a transfer executes, any legacy metadata is converted to a StockMovement record (lines 289–306), but this one-way conversion happens after the dropdown is rendered, leaving products with only metadata invisible in the dropdown until first transferred.

The core filter logic is correct and consistent; the issues are in dropdown visibility and legacy data handling.

---

## Issues (3)

1. **Dropdown filter mismatch: categories() does not apply stock filters** — Products visible in transfer dropdown depend on net StockMovement balance, but the raw query at lines 207–214 sums all direction-signed movements without applying `AVAILABLE_QTY_EXCLUDED_INBOUND` / `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` filters. A product with +50 (opening), -30 (production_consumption as out), -20 (sale as out) would show raw balance of 0 and fail the `> 0` check, hiding it from categories(). But `getSourceBalance()` excludes production_consumption from inbound, yielding 50 - 20 = 30 available. Result: **confirmed** — the product would have available qty > 0 but would not appear in the dropdown. Fix: apply the same filter constants to the categories() balance query.

2. **Products with only metadata are invisible in transfer dropdowns** — When a user navigates to Transfer Products, only products with positive StockMovement balance appear in dropdowns (lines 207–214). Products recorded only in metadata (`manufacturing_quantity` > 0) will not appear. The code converts metadata to movements during executeTransfer() (lines 289–306), but only after the user selects the product. If the user refreshes the page before submitting the transfer, the conversion never happens and they lose access to the selected product. **Confirmed** behavioral gap: legacy metadata is invisible until first transferred. This may be intentional for data hygiene, but it's worth documenting that products cannot be transferred from manufacturing to other branches without first materializing them via a manufacturing action or a direct metadata-to-movement migration job.

3. **No asymmetry in purchase-type available qty** — In ManufacturingUserDashboard, when `sellType === 'purchase'`, both `sellProducts()` (lines 938–957) and `resolveAvailableQty()` (lines 974–989) use the same Purchase.quantity query to filter and calculate available stock. A product appears in the dropdown only if it has Purchase records with quantity > 0, and its available qty is the sum of those records. **Confirmed** consistent — no issue here.

---

<details>
<summary>Details</summary>

### Filter logic is correct and consistent

Both `getSourceBalance()` (ProductTransfer, lines 175–182) and `resolveAvailableQty()` for product type (ManufacturingUserDashboard, lines 1006–1012) use identical logic:
```php
$stockIn = (clone $query)->where('direction', 'in')
    ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
    ->sum('quantity');
$stockOut = (clone $query)->where('direction', 'out')
    ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
    ->sum('quantity');
return max(0, (float)$stockIn - (float)$stockOut);
```
Both exclude production_consumption and packing_consumption from inbound and count only sale, transfer_out, adjustment_decrease, and distribution from outbound. These filter constants (StockMovement.php, lines 50–56) prevent double-counting of consumption ledger entries.

### categories() dropdown does not apply filters

The categories() method (lines 203–221) builds a raw balance query without the filter constants:
```php
StockMovement::whereIn('location_id', $locationIds)
    ->selectRaw('item_id, SUM(CASE WHEN direction=\'in\' THEN quantity ELSE -quantity END) as bal')
    ->having('bal', '>', 0)
```
This sums all movements including non-deductible types (e.g., production_consumption as out direction counts as a deduction). If a product has consumption ledger entries, they reduce its raw balance incorrectly, causing it to fail the `> 0` filter even though `getSourceBalance()` would correctly show available qty > 0. **Confirmed issue**: Apply `AVAILABLE_QTY_EXCLUDED_INBOUND` and `AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND` to the categories() query.

### Metadata-only products hidden from dropdowns

Products with only metadata (no StockMovement records) will not appear in the dropdowns because `categories()` queries StockMovement only. The conversion in executeTransfer() (lines 289–306) materializes metadata to movements but only on transfer submission. Products sitting in metadata remain invisible in the dropdown until manually transferred. This is **confirmed** behavioral gap requiring either (a) an explicit migration job to convert legacy metadata to movements, or (b) re-enabling metadata in the categories() / products() queries if backwards compatibility is needed.

### Purchase type stock calculation is correct

ManufacturingUserDashboard's purchase-type path uses Purchase.quantity for both filtering dropdowns and calculating available qty. **Confirmed** consistent — no change needed.

</details>

---

<details>
<summary>File map</summary>

- **ProductTransfer.php** — `getSourceBalance()` (lines 164–182): replaced dual source with StockMovement-only calculation. `categories()` (lines 203–221) and `products()` (lines 224–237): populate dropdowns by StockMovement balance > 0 without applying filter constants (issue #1).
- **ManufacturingUserDashboard.php** — `resolveAvailableQty()` (lines 971–1015): for product type, returns net StockMovement only. Converts metadata to movements in `executeTransfer()` (lines 289–306), but this happens after dropdown render (issue #2).

</details>
