<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\SalesService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class OrderIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $paymentStatus = '';

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function mount(): void
    {
        if (session('success')) {
            $this->successMessage = session('success');
        }
        if (session('error')) {
            $this->errorMessage = session('error');
        }
    }

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedStatus(): void        { $this->resetPage(); }
    public function updatedPaymentStatus(): void { $this->resetPage(); }

    // ── Quick actions (mirrors BranchSellerDashboard) ─────────────────────

    public function approvePayment(int $orderId): void
    {
        $this->doAction($orderId, fn($o, $u) =>
            app(SalesService::class)->approvePayment($o, $u),
            "Payment approved for order #{orderId}."
        );
    }

    public function rejectPayment(int $orderId): void
    {
        $this->doAction($orderId, fn($o, $u) =>
            app(SalesService::class)->rejectPayment($o, $u),
            "Payment rejected for order #{orderId}."
        );
    }

    public function processOrder(int $orderId): void
    {
        $this->doAction($orderId, fn($o, $u) =>
            app(SalesService::class)->processOrder($o, $u),
            "Order #{orderId} moved to processing."
        );
    }

    public function completeOrder(int $orderId): void
    {
        $this->doAction($orderId, fn($o, $u) =>
            app(SalesService::class)->completeOrder($o, $u),
            "Order #{orderId} completed. Stock deducted."
        );
    }

    private function doAction(int $orderId, callable $fn, string $tpl): void
    {
        $this->successMessage = '';
        $this->errorMessage   = '';

        try {
            $order  = Order::with('items')->findOrFail($orderId);
            $this->assertAccess($order);
            $fn($order, auth()->user());
            $this->successMessage = str_replace('#{orderId}', $orderId, $tpl);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function assertAccess(Order $order): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->hasPermission('business.update')) return;

        $sellerBranchIds = $user->assignedBranchIds();
        $orderBranchIds  = $order->items->pluck('fulfilling_branch_id')
            ->push($order->branch_id)->filter()->unique()->values()->toArray();

        if (!empty($orderBranchIds) && empty(array_intersect($sellerBranchIds, $orderBranchIds))) {
            throw new \RuntimeException('You are not authorised to manage this order.');
        }
    }

    public function render()
    {
        $user  = auth()->user();
        $isGeneralManager = $user->can('business.update') && !$user->isSuperAdmin();

        $query = Order::with(['customer', 'branch', 'items'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            // Branch sellers see only their branch orders (NOT general manager)
            ->when(
                !$user->isSuperAdmin() && !$isGeneralManager && !$user->hasPermission('business.update'),
                fn($q) => $q->forBranch($user->assignedBranchIds()[0] ?? 0)
            )
            // Filters
            ->when($this->status,
                fn($q) => $q->where('status', $this->status))
            ->when($this->paymentStatus,
                fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->when($this->search, fn($q) =>
                $q->where('order_number', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn($c) =>
                      $c->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                  )
            )
            ->orderByDesc('placed_at');

        return view('livewire.admin.orders.index', [
            'orders' => $query->paginate(25),
        ])->title('Orders');
    }
}
