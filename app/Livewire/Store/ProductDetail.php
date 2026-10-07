<?php

namespace App\Livewire\Store;

use App\Services\SalesService;
use App\Services\StoreService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class ProductDetail extends Component
{
    public int    $productId;
    public float  $quantity = 1;
    public string $addedMessage = '';
    public bool   $inWishlist = false;

    public function mount(int $id): void
    {
        $this->productId = $id;
        // Verify it's publicly visible — will 404 if not
        app(StoreService::class)->getProductDetail($id);
        $this->inWishlist = in_array($id, app(StoreService::class)->getWishlist());
    }

    public function addToCart(): void
    {
        try {
            app(\App\Services\GuestCartService::class)->addToCart($this->productId, $this->quantity);
            $this->addedMessage = 'Added to cart!';
            $this->dispatch('cart-updated');
        } catch (\Throwable $e) {
            $this->addedMessage = $e->getMessage();
        }
    }

    public function toggleWishlist(): void
    {
        $store = app(StoreService::class);
        if ($this->inWishlist) {
            $store->removeFromWishlist($this->productId);
            $this->inWishlist = false;
        } else {
            $store->addToWishlist($this->productId);
            $this->inWishlist = true;
        }
        $this->dispatch('wishlist-updated');
    }

    public function render()
    {
        $store   = app(StoreService::class);
        $product = $store->getProductDetail($this->productId);

        // Related products (same category, same type)
        $related = \App\Models\Product::withoutGlobalScopes()
            ->where('status', \App\Models\Product::STATUS_ACTIVE)
            ->where('is_marketplace_visible', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $this->productId)
            ->limit(4)
            ->get();

        return view('livewire.store.product-detail', [
            'product' => $product,
            'public'  => $store->toPublicArray($product),
            'related' => $related,
        ])->title($product->name . ' — Al-Baraka Store');
    }
}
