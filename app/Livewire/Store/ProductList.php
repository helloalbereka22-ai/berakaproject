<?php

namespace App\Livewire\Store;

use App\Models\Business;
use App\Services\StoreService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.marketplace')]
class ProductList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    #[Url]
    public string $categoryId = '';
    #[Url]
    public string $businessId = '';
    #[Url]
    public string $type = '';
    #[Url]
    public string $minPrice = '';
    #[Url]
    public string $maxPrice = '';
    #[Url]
    public string $sortBy = 'name'; // name | price_asc | price_desc | newest

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedCategoryId(): void { $this->resetPage(); }
    public function updatedBusinessId(): void { $this->resetPage(); }
    public function updatedType(): void     { $this->resetPage(); }
    public function updatedMinPrice(): void { $this->resetPage(); }
    public function updatedMaxPrice(): void { $this->resetPage(); }

    public function addToWishlist(int $productId): void
    {
        app(StoreService::class)->addToWishlist($productId);
        $this->dispatch('wishlist-updated');
    }

    public function render()
    {
        $store  = app(StoreService::class);
        $query  = $store->marketplaceQuery([
            'search'      => $this->search,
            'category_id' => $this->categoryId ?: null,
            'business_id' => $this->businessId ?: null,
            'type'        => $this->type ?: null,
            'min_price'   => $this->minPrice !== '' ? $this->minPrice : null,
            'max_price'   => $this->maxPrice !== '' ? $this->maxPrice : null,
        ]);

        // Exclude manufactured products from Products page
        // Show only saleable products that are NOT finished_product or bottle_product types
        $query->whereNotIn('type', ['finished_product', 'bottle_product']);

        match ($this->sortBy) {
            'price_asc'  => $query->reorder('selling_price', 'asc'),
            'price_desc' => $query->reorder('selling_price', 'desc'),
            'newest'     => $query->reorder('created_at', 'desc'),
            default      => $query->reorder('sort_order')->orderBy('name'),
        };

        $wishlist   = app(StoreService::class)->getWishlist();
        $categories = $store->getMarketplaceCategories();
        $businesses = Business::active()->orderBy('name')->get(['id', 'name']);

        return view('livewire.store.product-list', [
            'products'      => $query->paginate(24),
            'categories'    => $categories,
            'businesses'    => $businesses,
            'wishlist'      => $wishlist,
            'productTypes'  => [
                'book'       => '📚 Books',
                'stationery' => '✏️ Stationery',
                'fashion'    => '👕 Fashion',
            ],
        ])->title('Products — Al-Baraka Store');
    }
}
