<?php

namespace App\Livewire\Business;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class InventoryInitializer extends Component
{
    use WithPagination;

    public $filter_business = '';
    public $search = '';

    // Arrays to hold edits before saving
    public $stock = [];
    public $opening = [];
    public $min_level = [];

    public function mount()
    {
        $this->authorizeSuperAdmin();
    }

    protected function authorizeSuperAdmin()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->roles()->where('name', 'Super Admin')->exists()) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function updatedFilterBusiness() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }

    public function loadProductData($products)
    {
        // Populate arrays for existing values if not already set
        foreach ($products as $p) {
            if (!isset($this->stock[$p->id])) $this->stock[$p->id] = $p->stock_quantity;
            if (!isset($this->opening[$p->id])) $this->opening[$p->id] = $p->opening_quantity;
            if (!isset($this->min_level[$p->id])) $this->min_level[$p->id] = $p->min_stock_level;
        }
    }

    public function saveInventory($productId)
    {
        $this->authorizeSuperAdmin();

        $product = Product::find($productId);
        if ($product) {
            $product->update([
                'stock_quantity' => $this->stock[$productId] ?? $product->stock_quantity,
                'opening_quantity' => $this->opening[$productId] ?? $product->opening_quantity,
                'min_stock_level' => $this->min_level[$productId] ?? $product->min_stock_level,
            ]);

            session()->flash('success_' . $productId, 'Inventory updated.');
        }
    }

    public function render()
    {
        $query = Product::with('business');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filter_business) {
            $query->where('business_id', $this->filter_business);
        }

        $products = $query->paginate(20);
        $this->loadProductData($products->items());

        return view('livewire.business.inventory-initializer', [
            'products' => $products,
            'businesses' => Business::all()
        ]);
    }
}

