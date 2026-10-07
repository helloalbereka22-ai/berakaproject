<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\SalesService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class OrderShow extends Component
{
    public int    $orderId;
    public string $rejectReason    = '';
    public string $successMessage  = '';
    public string $errorMessage    = '';

    // Controls the reject-reason modal
    public bool $showRejectModal = false;

    public function mount(int $id): void
    {
        $this->orderId = $id;
        $this->assertAccess();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    public function approvePayment(): void
    {
        $this->doAction(fn($o, $u) =>
            app(SalesService::class)->approvePayment($o, $u),
            'Payment approved. Order is now confirmed.'
        );
    }

    public function openRejectModal(): void
    {
        $this->showRejectModal = true;
        $this->rejectReason    = '';
    }

    public function confirmReject(): void
    {
        $this->doAction(fn($o, $u) =>
            app(SalesService::class)->rejectPayment($o, $u, $this->rejectReason),
            'Payment rejected.'
        );
        $this->showRejectModal = false;
        $this->rejectReason    = '';
    }

    public function processOrder(): void
    {
        $this->doAction(fn($o, $u) =>
            app(SalesService::class)->processOrder($o, $u),
            'Order moved to processing.'
        );
    }

    public function completeOrder(): void
    {
        $this->doAction(fn($o, $u) =>
            app(SalesService::class)->completeOrder($o, $u),
            'Order completed. Inventory deducted.'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function doAction(callable $fn, string $message): void
    {
        $this->successMessage = '';
        $this->errorMessage   = '';

        try {
            $order = $this->loadOrder();
            $fn($order, auth()->user());
            $this->successMessage = $message;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function loadOrder(): Order
    {
        return Order::with([
            'customer',
            'branch.business',
            'items.product.category',
            'items.fulfillingBranch',
            'invoice.payments',
            'deliveries',
            'paymentApprovedBy',
        ])->findOrFail($this->orderId);
    }

    private function assertAccess(): void
    {
        $user  = auth()->user();
        $order = Order::with('items')->findOrFail($this->orderId);

        if ($user->isSuperAdmin() || $user->hasPermission('business.update')) return;

        if ($order->business_id) {
            $userBusinessIds = $user->assignedBusinessIds();
            if (!in_array($order->business_id, $userBusinessIds)) {
                abort(403, 'Access denied.');
            }
        }

        $sellerBranchIds = $user->assignedBranchIds();
        $orderBranchIds  = $order->items->pluck('fulfilling_branch_id')
            ->push($order->branch_id)->filter()->unique()->values()->toArray();

        if (!empty($orderBranchIds) && empty(array_intersect($sellerBranchIds, $orderBranchIds))) {
            abort(403, 'You are not authorised to view this order.');
        }
    }

    public function render()
    {
        $order = $this->loadOrder();

        return view('livewire.admin.orders.show', compact('order'))
            ->title("Order {$order->order_number}");
    }
}
