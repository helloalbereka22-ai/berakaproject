# SellerSales: Show all stocked products, not just transfers

The `stockProducts()` and `categories()` methods now return all active products with stock in the branch's locations, regardless of origin. Previously, these methods filtered to only products that arrived via transfer movements. The fix aligns the SellerSales component with SaleableProductIndex behavior, ensuring users see the complete inventory of items available to sell.

The change removes an artificial restriction that prevented certain products (e.g., Tukur Azmud Soap) from appearing in the sales picker while they showed as in-stock in the inventory tables. Products can now be sourced via multiple channels—transfers, purchases, or opening stock—and all are displayed for sale without qualification.

**Watch for:**
- Category query now performs a nested subquery through stock movements; verify it doesn't introduce N+1 behavior or unintended query duplication.
- The product picker now sources products by balance aggregation across all locations in the branch; confirm the "pick best location" logic (highest qty_on_hand) correctly handles inventory splits across multiple location records.
- The 'purchase' branch (sellType = 'purchase') is unchanged; verify that purchase-only products still behave correctly and the logic doesn't regress.

**Verdict**: APPROVED

---

<details>
<summary>High-level view</summary>

The core change removes the transfer-movement filter from the product branch of `stockProducts()`, exposing all products with positive stock balance to the sales picker. Previously, products were restricted to those arriving via inter-branch transfers; now all sourcing methods (transfers, purchases, opening stock, production) are included, matching SaleableProductIndex behavior.

The `categories()` method mirrors this: when `sellType = 'product'`, it returns all categories that have stock in the branch's locations, not just transfer-sourced ones. The 'purchase' mode remains unchanged, preserving the distinct purchase-only workflow.

The post-fetch `.groupBy('id')->map()` logic selects the highest-stock location per product. This is correct for the multi-location inventory model—products can be split across locations, but the picker shows one "best" instance to avoid duplicate rows in the UI.

</details>

<details>
<summary>Issues (2)</summary>

1. **Ambiguous stockProducts grouping** — The query groups by `stock_movements.location_id` but the output map logic picks the location with the highest balance per product (`sortByDesc('qty_on_hand')->first()`). This is correct but not immediately obvious; if a product has stock in two locations, the query will return two rows (one per location), and the map collapses to the best one. Document this design or add a comment clarifying the multi-location collapse.

2. **Unit cost vs selling price ambiguity in docs** — The user message mentions confusion about "unit cost" at the inventory transfers page. While not a code issue, the SellerSales component stores both `cost_price` and `selling_price`, and the sale uses both. Ensure downstream documentation or tooltips clarify that "Unit Cost" in the inventory view is the cost used to calculate profit, not the selling price.

</details>

<details>
<summary>Details</summary>

**Transfer-origin filter removed from stockProducts()**

The query now sources products via all movement types, not just transfers. This means products sourced via purchase receipts, opening stock, or production output are eligible for sale alongside transferred items.

**Categories query for product mode**

When `sellType = 'product'`, the categories method returns all categories with stock in the branch's locations (via `havingRaw` balance check). This mirrors the pattern in `stockProducts()` and correctly exposes all selectable categories. The 'purchase' mode is unchanged: it returns book/stationery/fashion/general categories with no stock filter, preserving the purchase-only workflow.

**Per-location inventory collapse and sale deduction**

The `stockProducts()` query groups by `location_id` and returns multiple rows if a product spans locations. The post-fetch map selects the highest-stock location per product, showing the user one "best" instance. The location_id is passed through the cart to `InventoryService::recordSale()`, deducting stock from that specific location only. This is correct: sales affect only the picked location, not all locations holding the product.

**Consistency with SaleableProductIndex**

SellerSales now mirrors SaleableProductIndex's balance-aggregation pattern (`SUM(CASE WHEN direction = 'in' ... ) > 0`), ensuring a product that appears on the Saleable Products page also appears in the sales picker (for the same branch context).

**Cart validation and race conditions**

The cart caches qty from the product picker, but the `sell()` method re-validates stock via `InventoryService::hasStock()` before commit, catching race conditions where another user reduces stock after the item is added to cart. This is appropriate for single-branch sales.

</details>

<details>
<summary>File map</summary>

- **SellerSales.php**: Removed transfer-movement filter from `stockProducts()` 'product' branch. Updated `categories()` to show all categories with stock in the branch's locations when `sellType = 'product'`. Purchase mode unchanged.

See the full diff against the base branch to review exact line changes.

</details>
