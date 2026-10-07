<?php

namespace App\Livewire\Business\Products;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductViewer extends Component
{
    use WithPagination;

    public $businessId;
    public $branchId;
    
    public $search = '';

    public function mount()
    {
        // Mock data. In reality: auth()->user()->business_id
        $this->businessId = 1;
        $this->branchId = 1;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Business Managers can only view products where they have an inventory record
        $inventories = Product::with(['category'])
            ->join('stock_movements', function ($join) {
                $join->on('products.id', '=', 'stock_movements.item_id')
                     ->where('stock_movements.item_type', 'product');
            })
            ->where('stock_movements.business_id', $this->businessId)
            ->where('stock_movements.branch_id', $this->branchId)
            ->where(function($query) {
                $query->where('products.name', 'like', '%' . $this->search . '%')
                      ->orWhere('products.sku', 'like', '%' . $this->search . '%');
            })
            ->select(
                'products.*',
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as quantity_on_hand")
            )
            ->groupBy('products.id')
            ->paginate(12);

        return view('livewire.business.products.product-viewer', [
            'inventories' => $inventories
        ]);
    }
}
