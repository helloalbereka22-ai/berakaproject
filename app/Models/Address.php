<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Address extends Model {
    protected $fillable = ['customer_id', 'address_type', 'country', 'region', 'city', 'sub_city', 'street', 'postal_code', 'additional_information', 'is_default'];
    public function customer() { return $this->belongsTo(Customer::class); }
}