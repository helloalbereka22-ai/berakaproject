<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'business_id',
        'product_id',
        'fulfilling_branch_id',
        'warehouse_location_id',
        'quantity',
        'unit_price',
        'original_price',
        'cost_price_snapshot',
        'discount_amount',
        'discount_type',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'quantity'            => 'decimal:4',
        'unit_price'          => 'decimal:4',
        'original_price'      => 'decimal:4',
        'cost_price_snapshot' => 'decimal:4',
        'discount_amount'     => 'decimal:4',
        'total_amount'        => 'decimal:4',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fulfillingBranch()
    {
        return $this->belongsTo(Branch::class, 'fulfilling_branch_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function warehouseLocation()
    {
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }

    public function getLineProfit(): float
    {
        return (float) $this->total_amount - ((float) $this->cost_price_snapshot * (float) $this->quantity);
    }
}
