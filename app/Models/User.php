<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('business_id', 'branch_id')
            ->withTimestamps();
    }

    // -----------------------------------------------
    // Role Checks
    // -----------------------------------------------

    /**
     * Check if the user has a specific role (by name).
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Check if the user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Check if the user is an Admin or staff with admin panel access.
     */
    public function isAdmin(): bool
    {
        // Any user with at least one assigned role has admin panel access.
        // Route-level permissions and tab visibility control what they can see.
        return $this->roles->count() > 0;
    }

    // -----------------------------------------------
    // Permission Checks
    // -----------------------------------------------

    /**
     * Check if the user has a specific permission.
     * Super Admin has all permissions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return Cache::remember("user_{$this->id}_permissions", 300, function () {
            return $this->roles
                ->flatMap(fn($r) => $r->permissions->pluck('name'))
                ->unique()
                ->values();
        })->contains($permission);
    }

    /**
     * Check if the user can perform any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    // -----------------------------------------------
    // Data Scope Helpers
    // -----------------------------------------------

    /**
     * Get the business IDs the user is assigned to.
     * Super Admin gets all business IDs.
     */
    public function assignedBusinessIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Business::pluck('id')->toArray();
        }

        return $this->roles
            ->pluck('pivot.business_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get the primary assigned business ID (for scoping queries).
     * Returns null for Super Admin (means global access).
     */
    public function assignedBusinessId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return null; // Super Admin: no restriction
        }

        return $this->roles->first()?->pivot->business_id;
    }

    /**
     * Get the branch IDs the user is assigned to.
     * For branch-level access only (Sellers, Staff, specific branch manufacturers).
     */
    public function assignedBranchIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Branch::pluck('id')->toArray();
        }

        return $this->roles
            ->pluck('pivot.branch_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Returns a query scope builder that already filters by the user's business scope.
     * Usage: $user->scopeQuery(Product::query())->get();
     */
    public function scopeQuery($query, string $businessColumn = 'business_id')
    {
        if ($this->isSuperAdmin()) {
            return $query;
        }

        $businessIds = $this->assignedBusinessIds();

        if (empty($businessIds)) {
            return $query->whereRaw('1=0'); // No businesses assigned, return empty.
        }

        return $query->whereIn($businessColumn, $businessIds);
    }

    /**
     * Flush the user's cached permissions.
     */
    public function flushPermissionCache(): void
    {
        Cache::forget("user_{$this->id}_permissions");
    }
}
