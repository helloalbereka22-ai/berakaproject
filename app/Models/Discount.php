<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Discount extends Model {
    protected $fillable = ['name', 'code', 'type', 'value', 'minimum_order_amount', 'maximum_discount_amount', 'starts_at', 'ends_at', 'status'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
}