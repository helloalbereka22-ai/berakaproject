<?php

namespace App\Livewire\Store;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class AccountOrderDetail extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        if (!auth('customer')->check()) {
            $this->redirectRoute('customer.login');
            return;
        }

        $this->orderNumber = $orderNumber;

        // Verify ownership immediately
        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order || $order->customer_id !== auth('customer')->id()) {
            $this->redirectRoute('store.account.orders');
        }
    }

    public function render()
    {
        $order = Order::with([
            'items.product.category',
            'items.product.primaryImage',
            'invoice.payments',
            'deliveries',
            'customer',
        ])
            ->where('order_number', $this->orderNumber)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        return view('livewire.store.account-order-detail', compact('order'))
            ->title("Order {$order->order_number} — Al-Baraka Store");
    }
}
