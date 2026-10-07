<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(
        private readonly AuthorizationService $authService,
        private readonly SalesService         $salesService,
    ) {}

    // ── List orders ───────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Order::with(['customer', 'invoice', 'branch'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('business_id'), function ($q) use ($request, $user) {
                $this->authService->assertBusinessAccess($user, (int) $request->business_id);
                $q->where('business_id', $request->business_id);
            })
            // Branch sellers automatically scoped to their assigned branches
            ->when(
                !$user->isSuperAdmin() && !$user->hasPermission('business.update'),
                function ($q) use ($user) {
                    $branchIds = $user->assignedBranchIds();
                    if (!empty($branchIds)) {
                        $q->forBranch($branchIds[0]); // primary branch
                    }
                }
            )
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->forBranch((int) $request->branch_id);
            })
            ->when($request->filled('status'),         fn($q) => $q->where('status', $request->status))
            ->when($request->filled('payment_status'), fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('customer_id'),    fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('from'),           fn($q) => $q->where('placed_at', '>=', $request->from))
            ->when($request->filled('to'),             fn($q) => $q->where('placed_at', '<=', $request->to . ' 23:59:59'))
            ->orderByDesc('placed_at');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(OrderResource::collection($query->paginate($perPage)));
    }

    // ── Place order (checkout from cart) ──────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'         => 'required|integer|exists:customers,id',
            'delivery_address_id' => 'nullable|integer|exists:addresses,id',
            'notes'               => 'nullable|string|max:1000',
            'discount_code'       => 'nullable|string|max:50',
        ]);

        try {
            $result = $this->salesService->checkout(
                customerId:        (int) $data['customer_id'],
                deliveryAddressId: isset($data['delivery_address_id'])
                    ? (int) $data['delivery_address_id'] : null,
                orderDiscountCode: $data['discount_code'] ?? null,
                notes:             $data['notes'] ?? '',
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(
            new OrderResource($result['order']->load(['items.product', 'invoice'])),
            'Order placed successfully. Awaiting payment approval.'
        );
    }

    // ── Get order ─────────────────────────────────────────────────────────

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        return $this->ok(
            new OrderResource(
                $order->load(['items.product.category', 'invoice.payments', 'deliveries', 'customer', 'branch'])
            )
        );
    }

    // ── Update order (notes / status) ─────────────────────────────────────

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        $data = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $original = $order->only('notes');
        $order->update($data);

        AuditService::logUpdate(Order::class, $order, $original, $order->business_id);

        return $this->ok(new OrderResource($order->load(['items', 'invoice'])));
    }

    // ── Cancel order ──────────────────────────────────────────────────────

    public function destroy(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        if ($order->status === Order::STATUS_COMPLETED) {
            return $this->error('Completed orders cannot be cancelled.', 409);
        }
        if ($order->status === Order::STATUS_PROCESSING) {
            return $this->error('Orders in processing cannot be cancelled. Complete or contact support.', 409);
        }

        AuditService::logDelete(Order::class, $order, $order->business_id);
        $order->update(['status' => Order::STATUS_CANCELLED]);

        return $this->ok(null, 'Order cancelled.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SELLER PAYMENT WORKFLOW ACTIONS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * PATCH /orders/{order}/approve-payment
     * Seller manually approves the customer payment.
     * Requires: permission payment.approve + branch access.
     */
    public function approvePayment(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        if (!$request->user()->hasPermission('payment.approve')) {
            return $this->error('You do not have permission to approve payments.', 403);
        }

        try {
            $updated = $this->salesService->approvePayment($order, $request->user());
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->ok(
            new OrderResource($updated->load(['items.product', 'invoice', 'branch'])),
            'Payment approved. Order is now confirmed.'
        );
    }

    /**
     * PATCH /orders/{order}/reject-payment
     * Seller rejects the customer payment.
     * Requires: permission payment.approve + branch access.
     */
    public function rejectPayment(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        if (!$request->user()->hasPermission('payment.approve')) {
            return $this->error('You do not have permission to manage payments.', 403);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $this->salesService->rejectPayment(
                $order,
                $request->user(),
                $data['reason'] ?? ''
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->ok(
            new OrderResource($updated->load(['items.product', 'invoice'])),
            'Payment rejected.'
        );
    }

    /**
     * PATCH /orders/{order}/process
     * Seller moves the order to processing state.
     * Pre-condition: payment must be approved.
     * Requires: permission order.process + branch access.
     */
    public function processOrder(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        if (!$request->user()->hasPermission('order.process')) {
            return $this->error('You do not have permission to process orders.', 403);
        }

        try {
            $updated = $this->salesService->processOrder($order, $request->user());
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->ok(
            new OrderResource($updated->load(['items.product', 'invoice'])),
            'Order moved to processing.'
        );
    }

    /**
     * PATCH /orders/{order}/complete
     * Seller completes the order.
     * THIS triggers the inventory deduction — happens exactly once.
     * Requires: permission order.process + branch access.
     */
    public function completeOrder(Request $request, Order $order): JsonResponse
    {
        $this->assertOrderAccess($request, $order);

        if (!$request->user()->hasPermission('order.process')) {
            return $this->error('You do not have permission to complete orders.', 403);
        }

        try {
            $updated = $this->salesService->completeOrder($order, $request->user());
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->ok(
            new OrderResource($updated->load(['items.product', 'invoice'])),
            'Order completed. Inventory updated.'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function assertOrderAccess(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) return;

        // Business-level access
        if ($order->business_id) {
            $this->authService->assertBusinessAccess($user, $order->business_id);
        }

        // Branch-level access: seller must belong to the order's branch
        if (!$user->hasPermission('business.update')) {
            $sellerBranchIds = $user->assignedBranchIds();
            $orderBranchIds  = $order->items()
                ->pluck('fulfilling_branch_id')
                ->push($order->branch_id)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!empty($orderBranchIds) && empty(array_intersect($sellerBranchIds, $orderBranchIds))) {
                abort(403, 'You are not authorised to access this order.');
            }
        }
    }
}
