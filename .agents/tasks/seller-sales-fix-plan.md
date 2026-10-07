# Implementation Plan: Fix SellerSales to Show All Saleable Products

## Problem Summary

The `SellerSales` component only shows products that arrived via **stock transfer movements** (reference_type = 'transfer'), excluding directly purchased bookstore products (books, oils, soaps, clothes, etc.). This creates a visibility discrepancy: products appear in `SaleableProductIndex` but not in `SellerSales` sales picker.

### Root Causes Identified

1. **stockProducts() computed method** (lines 115-200): The `else` branch (for sellType='product') has no filter, but earlier joins and groupBy logic inadvertently limit results. The query structure only processes products that have stock_movements, but then the subsequent collection grouping may miss products added via direct purchase rather than transfer.

2. **categories() computed method** (lines 79-105): When sellType='product', it filters by `reference_type = 'transfer'`, returning ONLY transferred products' categories. This excludes categories for purchased items (book, stationery, fashion, general) that should be visible.

3. **UI does not expose sellType toggle**: The blade view (seller-sales.blade.php) has no UI to switch between 'purchase' and 'product' modes. The sellType defaults to 'product' and is never changed by users.

### Solution

1. Remove the transfer-specific filter from `categories()` when sellType='product': return ALL categories that have stock in the branch's locations, matching `SaleableProductIndex` behavior.

2. Fix `stockProducts()` to ensure it returns ALL active products with positive stock in the branch's locations when sellType='product', without the implicit transfer-only restriction.

3. Since the UI does not expose sellType and it defaults to 'product', the 'purchase' branch becomes dead code. Consider marking it for future removal or leaving it for potential future use, but do not use it in the current implementation.

---

## Implementation Plan

- [ ] 1. **Fix the categories() computed method to show all categories with stock in sellType='product' mode.**

      **Rationale**: Currently when sellType='product', categories() filters by reference_type='transfer', excluding purchased items' categories (books, clothes, oils, etc.). Match SaleableProductIndex: return all categories that have any stock (sum > 0) in the branch's locations.

      **Files to modify**: `app/Livewire/Admin/Seller/SellerSales.php` (categories method, lines 79-105)

      **Changes**:
      - When sellType='product', change the query to NOT filter by reference_type='transfer'
      - Keep the same location-based and category-based aggregation logic
      - Exclude category types only for 'purchase' mode; for 'product' mode, show all categories regardless of type
      - Result: any category with products having stock > 0 in branch locations is included

      **Verify**: Run tests for the SellerSales component if they exist, or manually confirm in a local env that selecting different categories in the UI populates with both transferred AND purchased items' categories.

- [ ] 2. **Fix the stockProducts() computed method to show all products with stock when sellType='product'.**

      **Rationale**: stockProducts() joins with StockMovement and groups by product, but the existing logic should already work for both transferred and purchased items since it doesn't explicitly filter by reference_type for 'product' mode. However, verify that the query returns products with positive balances (sum(quantity) > 0) regardless of how the stock arrived.

      **Files to modify**: `app/Livewire/Admin/Seller/SellerSales.php` (stockProducts method, lines 115-200)

      **Changes**:
      - Ensure the query does NOT filter by reference_type when sellType='product'
      - Verify the HAVING clause correctly sums in and out movements to compute net stock
      - If the issue is in collection grouping at line 185+, ensure it correctly preserves all rows and collapses by highest balance

      **Verify**: Confirm in browser that the product picker dropdown shows both transferred items (oils, raw materials) AND purchased items (books, clothes) when filtering by category. Qty displayed should match the available qty in `SaleableProductIndex`.

- [ ] 3. **Remove dead code or mark for future: the sellType='purchase' branch (optional).**

      **Rationale**: The blade view (seller-sales.blade.php) has no UI to toggle sellType; it defaults to 'product' always. The 'purchase' branch in both categories() and stockProducts() is never executed by users. Leaving it does not hurt, but clarify intent: is it for future feature or legacy?

      **Files to check**: 
      - `resources/views/livewire/admin/seller/sales.blade.php` (no sellType toggle UI visible)
      - `app/Livewire/Admin/Seller/SellerSales.php` (sellType default and toggles)

      **Decision**: Leave the 'purchase' branch in place for now (no harm) but add a code comment documenting that 'purchase' mode is not exposed in the UI and was not tested. If later features need it, the code is ready.

      **Verify**: Inspect blade view to confirm no UI element allows users to change sellType from 'product'. If found, either implement the toggle properly or remove the dead branch.

---

## Files to Modify

1. **app/Livewire/Admin/Seller/SellerSales.php**
   - categories() method (lines 79–105)
   - stockProducts() method (lines 115–200)

---

## Verification Checklist

After implementing the changes:

1. **Manual UI Test in Browser**:
   - Navigate to http://localhost/albarekaaaaaa/public/admin/seller/sales
   - Verify the category dropdown includes categories for both transferred items AND purchased items (books, oils, clothes)
   - Select each category and confirm products appear in the dropdown
   - Verify qty on hand matches the qty shown in SaleableProductIndex

2. **Compare with SaleableProductIndex**:
   - Open http://localhost/albarekaaaaaa/public/admin/saleable-products in a separate tab
   - Cross-check: every product shown there should be selectable in SellerSales (if the branch has stock)
   - Qty should match

3. **Run Any Existing Tests** (if applicable):
   - `php artisan test --filter SellerSales` or equivalent
   - All tests should pass

4. **Edge Cases**:
   - Test with a branch that has NO transferred stock but has purchased stock → purchased items should appear
   - Test with a branch that has BOTH → all items should appear

---

## Notes

- The blade view does not expose sellType to users, so only 'product' mode is actively used. The 'purchase' mode may be legacy or for future expansion.
- SaleableProductIndex correctly shows all products with stock in user's branches, regardless of origin (purchase, transfer, etc.). This is the reference behavior.
- The fix ensures SellerSales mirrors that behavior for the product picker, closing the gap in visibility.
