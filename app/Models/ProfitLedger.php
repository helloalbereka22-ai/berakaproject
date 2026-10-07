<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfitLedger extends Model
{
    protected $table = 'profit_ledger';
    public $timestamps = false;

    protected $fillable = [
        'order_item_id',
        'business_id',
        'branch_id',
        'product_id',        // nullable — null for manufacturing/batch sales
        'selling_price',
        'cost_price',
        'quantity',
        'revenue',
        'total_cost',
        'profit',
        'recorded_at',
    ];

    protected $casts = [
        'selling_price' => 'decimal:4',
        'cost_price'    => 'decimal:4',
        'quantity'      => 'decimal:4',
        'revenue'       => 'decimal:4',
        'total_cost'    => 'decimal:4',
        'profit'        => 'decimal:4',
        'recorded_at'   => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
