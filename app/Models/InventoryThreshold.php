<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryThreshold extends Model
{
    protected $table = 'stock_thresholds';

    protected $fillable = [
        'business_id',
        'branch_id',
        'location_id',
        'item_type',
        'item_id',
        'reorder_point',
        'min_stock',
        'max_stock',
        'is_active',
    ];

    protected $casts = [
        'reorder_point' => 'decimal:4',
        'min_stock'     => 'decimal:4',
        'max_stock'     => 'decimal:4',
        'is_active'     => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function item()
    {
        return $this->morphTo();
    }
}
