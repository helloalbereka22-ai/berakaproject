<?php

namespace App\Livewire\Store;

use App\Models\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCountBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $customerId = auth('customer')->id();

        if ($customerId) {
            $cart        = Cart::where('customer_id', $customerId)
                ->where('status', 'active')
                ->first();
            $this->count = $cart ? (int) $cart->items()->count() : 0;
        } else {
            $this->count = 0;
        }
    }

    public function render()
    {
        return view('livewire.store.cart-count-badge');
    }
}
