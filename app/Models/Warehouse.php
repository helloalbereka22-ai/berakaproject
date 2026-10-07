<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Business;
use App\Models\Branch;

use App\Models\StockMovement;

class Warehouse extends Model
{
    protected $table = 'locations';

    protected $fillable = ['business_id', 'branch_id', 'name', 'code', 'type', 'status', 'description', 'address'];

    protected $casts = ['status' => 'string'];

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'location_id');
    }
}

