<?php

namespace App\Livewire\Admin;

use App\Category;
use App\Product;
use App\Services\ProductService;
use Livewire\Component;

class ProductForm extends Component
{
    public $categories = [];
    public $categoryId = null;
    
    // Core Product Fields
    public $name;
    public $slug;
    public $sku;
    public $barcode;
    public $description;
    public $initialStock = 0;
    
    // Dynamic Attributes
    public $dynamicAttributes = [];
    public $attributeValues = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'categoryId' => 'required|exists:categories,id',
        'sku' => 'nullable|string',
        'barcode' => 'nullable|string',
        'initialStock' => 'numeric|min:0',
    ];

    public function mount()
    {
        $this->categories = Category::where('is_active', true)->get();
    }

    public function updatedCategoryId($value)
    {
        $this->dynamicAttributes = [];
        $this->attributeValues = [];
        
        if ($value) {
            $category = Category::with('attributes')->find($value);
            if ($category) {
                $this->dynamicAttributes = $category->attributes;
                // Initialize default values
                foreach ($this->dynamicAttributes as $attr) {
                    $this->attributeValues[$attr->slug] = $attr->default_value;
                }
            }
        }
    }

    public function save(ProductService $productService)
    {
        // 1. Validate core fields
        $this->validate();
        
        // 2. Validate dynamic attributes dynamically
        $dynamicRules = [];
        foreach ($this->dynamicAttributes as $attr) {
            if ($attr->is_required) {
                $dynamicRules['attributeValues.' . $attr->slug] = 'required';
            }
            // Add custom rules if defined in JSON
            if ($attr->validation_rules) {
                // e.g. "numeric", "email"
                $dynamicRules['attributeValues.' . $attr->slug] .= '|' . implode('|', $attr->validation_rules);
            }
        }
        
        if (!empty($dynamicRules)) {
            $this->validate($dynamicRules);
        }
        
        // 3. Save Product and handle duplicates / stock via service
        // For demonstration, assume businessId = 1, locationId = 1, userId = auth user
        $businessId = 1;
        $locationId = 1;
        $userId = 1;
        
        $productData = [
            'name' => $this->name,
            'slug' => \Str::slug($this->name),
            'category_id' => $this->categoryId,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'attributes_data' => $this->attributeValues,
        ];
        
        try {
            $productService->createOrIncrementProduct(
                $businessId,
                $productData,
                $locationId,
                $this->initialStock,
                $userId
            );
            
            session()->flash('success', 'Product saved successfully!');
            return redirect()->route('admin.products.index');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.product-form');
    }
}
