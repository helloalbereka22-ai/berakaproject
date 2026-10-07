<?php

namespace App\Livewire\Store;

use App\Services\SalesService;
use App\Services\StoreService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class WishlistPage extends Component
{
    public function removeFromWishlist(int $productId): void
    {
        app(StoreService::class)->removeFromWishlist($productId);
        $this->dispatch('wishlist-updated');
    }

    public function moveToCart(int $productId): void
    {
        $customerId = auth('customer')->id();
        if (!$customerId) {
            $this->redirectRoute('customer.login');
            return;
        }
        try {
            app(SalesService::class)->addToCart($customerId, $productId, 1);
            app(StoreService::class)->removeFromWishlist($productId);
            $this->dispatch('cart-updated');
            $this->dispatch('wishlist-updated');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $products = app(StoreService::class)->getWishlistProducts();

        return view('livewire.store.wishlist', compact('products'))
            ->title('Wishlist — Al-Baraka Store');
    }
}
