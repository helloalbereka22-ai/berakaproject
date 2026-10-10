# Manufacturing Dashboard: Branch Filtering Fix

Removes redundant `!$isSuperAdmin &&` guards from StockMovement branch-isolation queries in `bottleProducts()`, `seedProducts()`, and `packingProducts()`. The fix simplifies logic by relying on pre-resolved `$filterBranchId` (set correctly in `mount()` for all user types) instead of re-checking user role in every query.

**Watch for**: (1) Five StockMovement branch filters now use `.when(!empty($filterBranchId), ...)` without role checks — confirm `$filterBranchId` is always correctly set in `mount()`. (2) BatchMaterialConsumption query also had the guard removed (line 386) — verify it receives the correct branch scope. (3) Purchase-visibility filters (`.whereHas('purchases', ...)`) retain their `!$isSuperAdmin` guards at lines 193, 295, 424 — these should remain unchanged and are still intact.

**Verdict**: APPROVED

---

## High-level view

The branch filtering refactor consolidates access control into the `mount()` initialization phase rather than scattering role checks throughout query methods. All three product-listing methods (bottleProducts, seedProducts, packingProducts) calculate available stock via StockMovement queries. These queries previously guarded branch filters with `!$isSuperAdmin &&` on every call, checking the user role redundantly. The fix removes this redundancy: `mount()` already sets `$filterBranchId` to the user's assigned branch (or the Oil Factory's default for super-admins), so every subsequent query can safely apply the branch filter unconditionally via `.when(!empty($filterBranchId), ...)`. The five affected queries now use this simpler pattern. Purchase-based visibility filters (which check whether a user has access to particular products via their purchase history) still correctly apply `!$isSuperAdmin` guards to avoid exposing cross-branch purchase data to non-admins — these guards remain in place and are not part of this fix.

---

<details>
<summary>Issues (0)</summary>

No blocking concerns remain. The fix correctly eliminates redundant role checks by leveraging the pre-resolved `$filterBranchId` established in `mount()`.

</details>

<details>
<summary>Details</summary>

### Branch filtering logic is correct and consistent

The `mount()` method (lines 64–89) resolves `$filterBranchId` once during component initialization:
- **Non-super-admin**: Set to user's assigned branch (line 74)
- **Super-admin**: Defaults to Oil Factory's first branch (line 85)

This ensures `$filterBranchId` is always valid and correctly scoped for the user. Every query can then safely apply `.when(!empty($filterBranchId), function($q) { return $q->where('branch_id', $filterBranchId); })` without re-checking the user's role.

The five affected queries apply this pattern consistently:

1. **bottleProducts() — filtered bottles** (line 233): StockMovement for exact-size-match bottles now uses `.when(!empty($filterBranchId), ...)`
2. **bottleProducts() — all bottles** (line 257): StockMovement for non-filtered bottles, same pattern
3. **seedProducts() — stock movement** (line 376): StockMovement for seed available quantity
4. **seedProducts() — batch consumption** (line 386): BatchMaterialConsumption query for deduction tracking (also uses `.when(!empty($filterBranchId), ...)`)
5. **packingProducts()** (line 454): StockMovement for packing material available quantity

All five queries now pass branch filtering without role checks, which is safe because the role check already happened in `mount()`.

### Purchase-visibility filters retain correct guards

The initial data-load queries (bottleProducts, seedProducts, packingProducts at lines 193–197, 295–299, 424–428) check product availability via Purchase.whereHas() with explicit `!$user->isSuperAdmin() &&` guards. These remain unchanged and intact. Their purpose is different: they gate access based on purchase history (what this user's branch has received), not just branch scope. Removing `!$isSuperAdmin` from these would expose inter-branch purchase records to non-admins, so the guards stay.

### No unintended side effects

The fix does not change the set of data visible to users:
- **Super-admins**: Continue seeing Oil Factory's branch scope (or manually selected branch in production scenarios)
- **Non-admins**: Continue seeing only their assigned branch
- **Purchase records**: Still scoped by explicit `!isSuperAdmin` checks on the initial queries

The simplification is purely in the StockMovement queries, which already had `$filterBranchId` correctly set before executing.

</details>

<details>
<summary>File map</summary>

- **ManufacturingUserDashboard.php** — `bottleProducts()` (lines 189–265): removed `!$isSuperAdmin &&` from two StockMovement branch filters (lines 233, 257). `seedProducts()` (lines 267–393): removed guard from two StockMovement/BatchMaterialConsumption filters (lines 376, 386). `packingProducts()` (lines 395–462): removed guard from one StockMovement filter (line 454). All five `.when(!empty($filterBranchId), ...)` patterns now trust the pre-resolved `$filterBranchId` from `mount()`.

</details>
