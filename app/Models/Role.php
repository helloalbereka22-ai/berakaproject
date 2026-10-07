<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot('business_id', 'branch_id')
            ->withTimestamps();
    }

    /**
     * Get all permission names for this role (cached).
     */
    public function getPermissionNamesAttribute(): array
    {
        return Cache::remember("role_{$this->id}_permissions", 300, function () {
            return $this->permissions->pluck('name')->toArray();
        });
    }

    /**
     * Flush the cached permissions for this role.
     */
    public function flushPermissionCache(): void
    {
        Cache::forget("role_{$this->id}_permissions");
    }
}
