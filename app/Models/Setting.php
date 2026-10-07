<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {
    protected $fillable = ['business_id', 'branch_id', 'key', 'value', 'type'];
    public function business() { return $this->belongsTo(Business::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
}