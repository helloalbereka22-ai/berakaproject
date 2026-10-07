<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGood extends Model
{
    const STATUS_AVAILABLE   = 'available';
    const STATUS_DISTRIBUTED = 'distributed';
    const STATUS_RESERVED    = 'reserved';

    protected $fillable = [
        'production_batch_id',
        'product_id',
        'quantity',
        'quantity_available',
        'quantity_distributed',
        'quantity_sold',
        'quantity_returned',
        'unit_cost',
        'warehouse_location_id',
        'status',
    ];

    protected $casts = [
        'quantity'              => 'decimal:4',
        'quantity_available'    => 'decimal:4',
        'quantity_distributed'  => 'decimal:4',
        'quantity_sold'         => 'decimal:4',
        'quantity_returned'     => 'decimal:4',
        'unit_cost'             => 'decimal:4',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouseLocation()
    {
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }

    public function distributions()
    {
        return $this->hasMany(Distribution::class, 'finished_good_id');
    }

    public function getRemaining(): float
    {
        return max(0, (float) $this->quantity_available
            - (float) $this->quantity_distributed
            - (float) $this->quantity_sold
            + (float) $this->quantity_returned);
    }
}
