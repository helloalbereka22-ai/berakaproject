<?php

namespace App\Livewire\Business\BookStore;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class CatalogView extends Component
{
    use WithPagination;

    public $businessId;
    public $search = '';

    public function mount()
    {
        $this->businessId = auth()->user()->roles()->first()->pivot->business_id ?? 1;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::whereHas('businesses', function ($q) {
                $q->where('businesses.id', $this->businessId);
            })
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->with(['media', 'categories'])
            ->paginate(12);

        return view('livewire.business.book-store.catalog-view', [
            'products' => $products
        ]);
    }
}
