<?php

namespace App\Livewire\Store;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class OrderConfirmation extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;

        // If guest somehow lands here without a matching order, redirect to store
        $customer = auth('customer')->user();
        $order    = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            $this->redirectRoute('store.index');
            return;
        }

        // Ensure order belongs to this customer (security check)
        if ($customer && $order->customer_id !== $customer->id) {
            $this->redirectRoute('store.index');
        }
    }

    public function render()
    {
        $order = Order::with([
            'items.product.category',
            'invoice.payments',
            'deliveries',
            'customer',
        ])->where('order_number', $this->orderNumber)->firstOrFail();

        return view('livewire.store.order-confirmation', compact('order'))
            ->title('Order Confirmed — Al-Baraka Store');
    }
}
