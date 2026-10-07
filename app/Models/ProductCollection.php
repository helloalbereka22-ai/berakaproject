<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
use App\Models\Business;

class ProductCollection extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'description',
        'cover_image_path',
        'display_order',
        'is_active',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot('sort_order')
            ->orderBy('collection_product.sort_order');
    }
}

