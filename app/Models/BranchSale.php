<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BranchSale extends Model {
    protected $fillable = ['branch_id', 'location_id', 'production_batch_id', 'product_id', 'quantity', 'unit_price', 'cost_price', 'total_amount', 'sale_date', 'recorded_by'];
    protected $casts = ['sale_date' => 'date'];
    public function branch() { return $this->belongsTo(Branch::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function batch() { return $this->belongsTo(ProductionBatch::class, 'production_batch_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}