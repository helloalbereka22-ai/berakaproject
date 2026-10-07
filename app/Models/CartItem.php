<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'business_id',
        'product_id',
        'quantity',
        'unit_price',
        'original_price',
        'discount_amount',
        'final_price',
        'discount_type',
    ];

    protected $casts = [
        'quantity'        => 'decimal:4',
        'unit_price'      => 'decimal:4',
        'original_price'  => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'final_price'     => 'decimal:4',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function getLineTotal(): float
    {
        return round((float) $this->final_price * (float) $this->quantity, 4);
    }
}
