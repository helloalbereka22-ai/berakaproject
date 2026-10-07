<?php

namespace App\Livewire\Store;

use App\Services\SalesService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class CartPage extends Component
{
    public string $discountCode = '';
    public string $discountMessage = '';

    protected function customerId(): ?int
    {
        return auth('customer')->id();
    }

    public function updateQuantity(int $productId, float $qty): void
    {
        $customerId = $this->customerId();
        if (!$customerId) return;

        if ($qty <= 0) {
            app(SalesService::class)->removeFromCart($customerId, $productId);
        } else {
            app(SalesService::class)->addToCart($customerId, $productId, $qty);
        }
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $productId): void
    {
        $customerId = $this->customerId();
        if (!$customerId) return;
        app(SalesService::class)->removeFromCart($customerId, $productId);
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $customerId = $this->customerId();
        if (!$customerId) return;

        $summary = app(SalesService::class)->getCartSummary($customerId, $this->discountCode);
        if ($summary['order_discount'] > 0) {
            session()->put('cart_discount_code', $this->discountCode);
            $this->discountMessage = "Discount applied: −" . number_format($summary['order_discount'], 2);
        } else {
            $this->discountMessage = 'Invalid or expired discount code.';
        }
    }

    public function checkout(): void
    {
        if (!$this->customerId()) {
            $this->redirectRoute('customer.login');
            return;
        }
        $this->redirectRoute('store.checkout');
    }

    public function render()
    {
        $customerId   = $this->customerId();
        $discountCode = session('cart_discount_code');
        $summary      = $customerId
            ? app(SalesService::class)->getCartSummary($customerId, $discountCode)
            : ['items' => [], 'item_count' => 0, 'subtotal' => 0, 'order_discount' => 0, 'total' => 0];

        // Eager-load product images for all cart items in one query
        $productIds  = collect($summary['items'])->pluck('product_id')->filter()->unique()->values();
        $cartImages  = \App\Models\Product::withoutGlobalScopes()
            ->whereIn('id', $productIds)
            ->with('primaryImage')
            ->get(['id'])
            ->keyBy('id');

        return view('livewire.store.cart', compact('summary', 'cartImages'))
            ->title('Cart — Al-Baraka Store');
    }
}
