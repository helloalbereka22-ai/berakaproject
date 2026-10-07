<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Models\Warehouse;

class AuthorizationService
{
    /**
     * Check if the user can access a given business.
     * Super Admin: always yes.
     * Others: only if they hold a role scoped to that business.
     */
    public function canAccessBusiness(User $user, int $businessId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()
            ->wherePivot('business_id', $businessId)
            ->exists();
    }

    /**
     * Check if the user can access a given branch.
     * Super Admin: always yes.
     * Business-level roles: yes if branch belongs to their business.
     * Branch-level roles: yes only if scoped to that branch.
     */
    public function canAccessBranch(User $user, int $branchId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Business-level role — can access all branches in their business
        $branch = Branch::find($branchId);
        if (!$branch) {
            return false;
        }

        $hasBusiness = $user->roles()
            ->wherePivot('business_id', $branch->business_id)
            ->wherePivotNull('branch_id')
            ->exists();

        if ($hasBusiness) {
            return true;
        }

        // Branch-level role — scoped directly
        return $user->roles()
            ->wherePivot('branch_id', $branchId)
            ->exists();
    }

    /**
     * Check if the user can access a given warehouse.
     * Warehouse must belong to a branch the user can access.
     */
    public function canAccessWarehouse(User $user, int $warehouseId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            return false;
        }

        return $this->canAccessBranch($user, $warehouse->branch_id);
    }

    /**
     * Scope a query to only businesses the user can access.
     * Returns the query unchanged for Super Admin.
     */
    public function scopeBusinessQuery($query, User $user, string $column = 'business_id')
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ids = $user->assignedBusinessIds();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $ids);
    }

    /**
     * Scope a query to only branches the user can access.
     */
    public function scopeBranchQuery($query, User $user, string $column = 'branch_id')
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ids = $user->assignedBranchIds();

        if (empty($ids)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $ids);
    }

    /**
     * Assert the user can access the given business — abort 403 otherwise.
     */
    public function assertBusinessAccess(User $user, int $businessId): void
    {
        if (!$this->canAccessBusiness($user, $businessId)) {
            abort(403, 'You do not have access to this business.');
        }
    }

    /**
     * Assert the user can access the given branch — abort 403 otherwise.
     */
    public function assertBranchAccess(User $user, int $branchId): void
    {
        if (!$this->canAccessBranch($user, $branchId)) {
            abort(403, 'You do not have access to this branch.');
        }
    }
}
