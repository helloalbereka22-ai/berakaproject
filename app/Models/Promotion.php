<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Promotion extends Model {
    protected $fillable = ['name', 'description', 'scope_type', 'scope_id', 'discount_id', 'starts_at', 'ends_at', 'status'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    public function discount() { return $this->belongsTo(Discount::class); }
}