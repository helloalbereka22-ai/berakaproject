<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class BusinessService
{
    /**
     * Create a new business with an optional initial branch and warehouse.
     */
    public function create(array $data): Business
    {
        return DB::transaction(function () use ($data) {
            $business = Business::create([
                'name'        => $data['name'],
                'code'        => $data['code'],
                'description' => $data['description'] ?? null,
                'status'      => $data['status'] ?? 'active',
            ]);

            AuditService::logCreate('App\\Models\\Business', $business);

            // Optionally create a default branch
            if (!empty($data['default_branch_name'])) {
                $this->addBranch($business, [
                    'name'   => $data['default_branch_name'],
                    'code'   => $data['default_branch_code'] ?? strtoupper(substr($data['name'], 0, 3)) . '-BR1',
                    'status' => 'active',
                ]);
            }

            return $business->fresh('branches');
        });
    }

    /**
     * Update an existing business.
     */
    public function update(Business $business, array $data): Business
    {
        return DB::transaction(function () use ($business, $data) {
            $original = $business->toArray();
            $business->update([
                'name'        => $data['name']        ?? $business->name,
                'code'        => $data['code']        ?? $business->code,
                'description' => $data['description'] ?? $business->description,
                'status'      => $data['status']      ?? $business->status,
            ]);

            AuditService::logUpdate('App\\Models\\Business', $business, $original);

            return $business->fresh();
        });
    }

    /**
     * Add a branch to a business.
     */
    public function addBranch(Business $business, array $data): Branch
    {
        return DB::transaction(function () use ($business, $data) {
            $branch = Branch::create([
                'business_id' => $business->id,
                'name'        => $data['name'],
                'code'        => $data['code'],
                'description' => $data['description'] ?? null,
                'status'      => $data['status'] ?? 'active',
            ]);

            AuditService::logCreate('App\\Models\\Branch', $branch, $business->id);

            // Create a default warehouse location for the branch
            if (!empty($data['create_default_warehouse'])) {
                Location::create([
                    'branch_id' => $branch->id,
                    'name'      => 'Main Warehouse',
                    'code'      => strtoupper(substr($branch->code ?? $branch->name, 0, 6)) . '-WH1',
                    'type'      => 'warehouse',
                    'status'    => 'active',
                ]);
            }

            return $branch->fresh();
        });
    }

    /**
     * Get businesses accessible to the given user.
     */
    public function getAccessibleBusinesses($user, array $filters = [])
    {
        $query = Business::withCount('branches')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('id', $user->assignedBusinessIds())
            )
            ->when(!empty($filters['status']), fn($q) =>
                $q->where('status', $filters['status'])
            )
            ->when(!empty($filters['search']), fn($q) =>
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('code', 'like', "%{$filters['search']}%")
            )
            ->orderBy('name');

        return $query;
    }

    /**
     * Get a summary of a business for the dashboard.
     */
    public function getSummary(Business $business): array
    {
        return [
            'branches'      => $business->branches()->count(),
            'active_branches' => $business->branches()->where('status', 'active')->count(),
            'locations'     => Location::whereHas('branch', fn($q) =>
                $q->where('business_id', $business->id)
            )->count(),
        ];
    }
}
