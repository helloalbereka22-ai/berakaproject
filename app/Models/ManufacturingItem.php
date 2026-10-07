<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManufacturingItem extends Model
{
    protected $fillable = [
        'business_id',
        'product_id',
        'category_id',
        'quantity',
        'added_by',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'quantity' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}