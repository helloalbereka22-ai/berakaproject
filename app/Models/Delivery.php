<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Delivery extends Model {
    protected $fillable = ['order_id', 'fulfilling_branch_id', 'delivery_address_id', 'delivery_date', 'status', 'tracking_number', 'notes', 'completed_at'];
    protected $casts = ['delivery_date' => 'date', 'completed_at' => 'datetime'];
    public function order() { return $this->belongsTo(Order::class); }
    public function fulfillingBranch() { return $this->belongsTo(Branch::class, 'fulfilling_branch_id'); }
    public function address() { return $this->belongsTo(Address::class, 'delivery_address_id'); }
}