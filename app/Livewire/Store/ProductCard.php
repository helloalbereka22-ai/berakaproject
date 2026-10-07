<?php

namespace App\Livewire\Store;

use App\Services\SalesService;
use Livewire\Component;

class ProductCard extends Component
{
    public int    $productId;
    public string $message   = '';
    public bool   $added     = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function addToCart(): void
    {
        $customerId = auth('customer')->id();

        if (!$customerId) {
            // Not logged in — redirect to login
            $this->redirect(route('login'));
            return;
        }

        try {
            app(SalesService::class)->addToCart($customerId, $this->productId, 1);
            $this->added   = true;
            $this->message = 'Added!';
            $this->dispatch('cart-updated');
        } catch (\Throwable $e) {
            $this->message = 'Error: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.store.partials.product-card-wire');
    }
}
