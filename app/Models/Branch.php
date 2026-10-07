<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'description',
        'manager_id',
        'status',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }
}
