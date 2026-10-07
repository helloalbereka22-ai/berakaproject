<?php

namespace App\Livewire\Business;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Business;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthorizationService;

class ProductManager extends Component
{
    public $products;
    public $categories;
    public $businesses;
    
    // Search and Filters
    public $search = '';
    public $filter_category = '';
    public $filter_business = '';

    // Form fields
    public $name, $description, $sku, $barcode, $selling_price, $cost_price, $unit_of_measure;
    public $category_id, $business_id;

    // Dynamic Attributes
    public $current_category_attributes = [];
    public $dynamic_attributes = [];

    public function mount()
    {
        $this->loadData();
    }

    public function updatedSearch() { $this->loadData(); }
    public function updatedFilterCategory() { $this->loadData(); }
    public function updatedFilterBusiness() { $this->loadData(); }

    public function updatedCategoryId($id)
    {
        if ($id) {
            $this->current_category_attributes = \App\Models\ProductAttribute::where('category_id', $id)->get();
            $this->dynamic_attributes = []; // reset
        } else {
            $this->current_category_attributes = [];
            $this->dynamic_attributes = [];
        }
    }

    public function loadData()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isSuperAdmin = $user->roles()->where('name', 'Super Admin')->exists();
        
        $this->categories = Category::all();

        $query = Product::with(['category', 'business']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filter_category) {
            $query->where('category_id', $this->filter_category);
        }

        if ($isSuperAdmin) {
            $this->businesses = Business::all();
            if ($this->filter_business) {
                $query->where('business_id', $this->filter_business);
            }
        } else {
            $businessIds = $user->roles()->pluck('role_user.business_id')->filter();
            $this->businesses = Business::whereIn('id', $businessIds)->get();
            $query->whereIn('business_id', $businessIds);
            
            if ($this->filter_business && $businessIds->contains($this->filter_business)) {
                $query->where('business_id', $this->filter_business);
            }
            
            if ($this->businesses->count() == 1) {
                $this->business_id = $this->businesses->first()->id;
            }
        }

        $this->products = $query->get();
    }

    public function saveProduct()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'business_id' => 'required|exists:businesses,id',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'unit_of_measure' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isSuperAdmin = $user->roles()->where('name', 'Super Admin')->exists();
        $businessIds = $user->roles()->pluck('role_user.business_id')->filter();

        if (!$isSuperAdmin && !$businessIds->contains($this->business_id)) {
            session()->flash('error', 'Unauthorized business assignment.');
            return;
        }

        $slug = Str::slug($this->name);
        $count = Product::where('slug', 'LIKE', $slug . '%')->count();
        if ($count > 0) {
            $slug = $slug . '-' . time();
        }

        $product = Product::create([
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'selling_price' => $this->selling_price,
            'cost_price' => $this->cost_price,
            'unit_of_measure' => $this->unit_of_measure,
            'category_id' => $this->category_id,
            'business_id' => $this->business_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'status' => 'active',
            'stock_quantity' => 0, // set via initialization
        ]);

        // Save dynamic attributes
        foreach ($this->dynamic_attributes as $attrId => $value) {
            if (!empty($value)) {
                \App\Models\AttributeValue::create([
                    'product_id' => $product->id,
                    'product_attribute_id' => $attrId,
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]);
            }
        }

        session()->flash('success', 'Product created successfully.');
        $this->reset(['name', 'description', 'sku', 'barcode', 'selling_price', 'cost_price', 'unit_of_measure', 'category_id', 'dynamic_attributes']);
        $this->current_category_attributes = [];
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.business.product-manager');
    }
}

