<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Deliveries
 * Manual delivery tracking. Business-scoped.
 */
class DeliveryController extends ApiController
{
    public function __construct(
        private readonly AuthorizationService $authService,
        private readonly SalesService         $salesService,
    ) {}

    /**
     * List deliveries
     *
     * @authenticated
     * @queryParam order_id int Filter by order.
     * @queryParam status string Filter by status (pending|in_transit|delivered|failed).
     * @queryParam business_id int Filter by business.
     * @queryParam from string Delivery date from (Y-m-d).
     * @queryParam to string Delivery date to (Y-m-d).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Delivery::with(['order.customer', 'fulfillingBranch', 'address'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('order', fn($oq) => $oq->whereIn('business_id', $businessIds));
            })
            ->when($request->filled('order_id'), fn($q) => $q->where('order_id', $request->order_id))
            ->when($request->filled('status'),   fn($q) => $q->where('status', $request->status))
            ->when($request->filled('from'),     fn($q) => $q->where('delivery_date', '>=', $request->from))
            ->when($request->filled('to'),       fn($q) => $q->where('delivery_date', '<=', $request->to))
            ->orderByDesc('delivery_date');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(DeliveryResource::collection($query->paginate($perPage)));
    }

    /**
     * Create delivery record
     *
     * Deliveries are recorded manually — no GPS/route optimization.
     *
     * @authenticated
     * @bodyParam order_id int required
     * @bodyParam address_id int required Delivery address ID.
     * @bodyParam fulfilling_branch_id int required
     * @bodyParam delivery_date date required Expected delivery date (Y-m-d).
     * @bodyParam notes string optional
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'             => 'required|integer|exists:orders,id',
            'address_id'           => 'required|integer|exists:addresses,id',
            'fulfilling_branch_id' => 'required|integer|exists:branches,id',
            'delivery_date'        => 'required|date',
            'notes'                => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($data['order_id']);
        if ($order->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $order->business_id);
        }

        try {
            $delivery = $this->salesService->recordDelivery(
                orderId:           (int) $data['order_id'],
                addressId:         (int) $data['address_id'],
                fulfillingBranchId:(int) $data['fulfilling_branch_id'],
                deliveryDate:      $data['delivery_date'],
                notes:             $data['notes'] ?? '',
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        AuditService::logCreate('App\\Models\\Delivery', $delivery, $order->business_id);

        return $this->created(new DeliveryResource($delivery->load(['order', 'fulfillingBranch'])));
    }

    /**
     * Get delivery
     *
     * @authenticated
     * @urlParam delivery int required The delivery ID.
     */
    public function show(Request $request, Delivery $delivery): JsonResponse
    {
        $this->assertDeliveryAccess($request, $delivery);

        return $this->ok(new DeliveryResource($delivery->load(['order.customer', 'fulfillingBranch', 'address'])));
    }

    /**
     * Update delivery status / info
     *
     * @authenticated
     * @urlParam delivery int required The delivery ID.
     * @bodyParam status string (pending|in_transit|delivered|failed).
     * @bodyParam delivery_date date optional.
     * @bodyParam notes string optional.
     */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        $this->assertDeliveryAccess($request, $delivery);

        $data = $request->validate([
            'status'        => 'sometimes|in:pending,in_transit,delivered,failed',
            'delivery_date' => 'sometimes|date',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $original = $delivery->toArray();

        if (($data['status'] ?? null) === 'delivered') {
            $delivery = $this->salesService->completeDelivery($delivery);
        } else {
            $delivery->update($data);
        }

        AuditService::logUpdate('App\\Models\\Delivery', $delivery, $original,
            $delivery->order?->business_id);

        return $this->ok(new DeliveryResource($delivery->load(['order', 'fulfillingBranch'])));
    }

    /**
     * Delete delivery record
     *
     * @authenticated
     * @urlParam delivery int required The delivery ID.
     */
    public function destroy(Request $request, Delivery $delivery): JsonResponse
    {
        $this->assertDeliveryAccess($request, $delivery);

        AuditService::logDelete('App\\Models\\Delivery', $delivery, $delivery->order?->business_id);
        $delivery->delete();

        return $this->ok(null, 'Delivery deleted.');
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function assertDeliveryAccess(Request $request, Delivery $delivery): void
    {
        $businessId = $delivery->order?->business_id;
        if ($businessId) {
            $this->authService->assertBusinessAccess($request->user(), $businessId);
        } elseif (!$request->user()->isSuperAdmin()) {
            abort(403, 'Access denied.');
        }
    }
}
