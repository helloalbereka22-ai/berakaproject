<?php

namespace App\Livewire\Store;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.marketplace')]
class AccountOrders extends Component
{
    use WithPagination;

    public function mount(): void
    {
        if (!auth('customer')->check()) {
            $this->redirectRoute('customer.login');
        }
    }

    public function render()
    {
        $customer = auth('customer')->user();

        $orders = Order::with(['items', 'invoice', 'deliveries'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('placed_at')
            ->paginate(10);

        return view('livewire.store.account-orders', compact('orders', 'customer'))
            ->title('My Orders — Al-Baraka Store');
    }
}
