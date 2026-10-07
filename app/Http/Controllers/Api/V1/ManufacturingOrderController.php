<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManufacturingOrderResource;
use App\Models\ManufacturingOrder;
use App\Services\AuthorizationService;
use App\Services\ManufacturingService;
use Illuminate\Http\Request;

class ManufacturingOrderController extends Controller
{
    public function __construct(
        private readonly ManufacturingService $manufacturing,
        private readonly AuthorizationService $authService,
    ) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = ManufacturingOrder::with(['product', 'business', 'branch', 'supervisor'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('business_id'), fn($q) =>
                $q->where('business_id', $request->business_id)
            )
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', $request->status)
            )
            ->orderByDesc('created_at');

        return ManufacturingOrderResource::collection($query->paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id'        => 'required|integer|exists:businesses,id',
            'branch_id'          => 'required|integer|exists:branches,id',
            'product_id'         => [
                'required',
                'integer',
                'exists:products,id',
                function ($attribute, $value, $fail) {
                    $product = \App\Models\Product::find($value);
                    if ($product && $product->variants()->exists()) {
                        $fail('You must select a specific variant, not the parent product.');
                    }
                }
            ],
            'package_size'       => 'nullable|string|max:50',
            'planned_quantity'   => 'required|numeric|min:0.0001',
            'status'             => 'nullable|in:pending,in_progress,completed,cancelled',
            'supervisor_id'      => 'nullable|integer|exists:users,id',
            'planned_start_date' => 'nullable|date',
            'planned_end_date'   => 'nullable|date',
            'notes'              => 'nullable|string|max:1000',
        ]);

        $this->authService->assertBusinessAccess($request->user(), (int) $data['business_id']);

        // SECURITY FIX (M4): Also verify the branch belongs to the specified business
        // and the user has access to it.
        $branch = \App\Models\Branch::findOrFail($data['branch_id']);
        if ($branch->business_id !== (int) $data['business_id']) {
            return response()->json([
                'success' => false,
                'message' => 'The branch does not belong to the specified business.',
            ], 422);
        }
        $this->authService->assertBranchAccess($request->user(), (int) $data['branch_id']);

        $order = $this->manufacturing->createOrder($data);

        return new ManufacturingOrderResource($order->load(['product', 'business', 'branch']));
    }

    public function show(Request $request, ManufacturingOrder $manufacturingOrder)
    {
        $this->authService->assertBusinessAccess($request->user(), $manufacturingOrder->business_id);

        return new ManufacturingOrderResource(
            $manufacturingOrder->load(['product', 'business', 'branch', 'supervisor', 'batches'])
        );
    }

    public function update(Request $request, ManufacturingOrder $manufacturingOrder)
    {
        $this->authService->assertBusinessAccess($request->user(), $manufacturingOrder->business_id);

        $data = $request->validate([
            'package_size'       => 'nullable|string|max:50',
            'planned_quantity'   => 'sometimes|numeric|min:0.0001',
            'status'             => 'sometimes|in:pending,in_progress,completed,cancelled',
            'supervisor_id'      => 'nullable|integer|exists:users,id',
            'planned_start_date' => 'nullable|date',
            'planned_end_date'   => 'nullable|date',
            'notes'              => 'nullable|string|max:1000',
        ]);

        $original = $manufacturingOrder->toArray();
        $manufacturingOrder->update($data);

        \App\Services\AuditService::logUpdate(
            'App\\Models\\ManufacturingOrder',
            $manufacturingOrder,
            $original,
            $manufacturingOrder->business_id
        );

        return new ManufacturingOrderResource($manufacturingOrder->fresh()->load('product'));
    }

    public function destroy(Request $request, ManufacturingOrder $manufacturingOrder)
    {
        // SECURITY FIX (C5): assertBusinessAccess was missing — IDOR vulnerability
        $this->authService->assertBusinessAccess($request->user(), $manufacturingOrder->business_id);

        if (!$request->user()->hasPermission('manufacturing.delete') && !$request->user()->isSuperAdmin()) {
            abort(403, 'You do not have permission to delete manufacturing orders.');
        }

        if ($manufacturingOrder->batches()->exists()) {
            return response()->json(['message' => 'Cannot delete an order that has production batches.'], 422);
        }

        \App\Services\AuditService::logDelete(
            'App\\Models\\ManufacturingOrder',
            $manufacturingOrder,
            $manufacturingOrder->business_id
        );

        $manufacturingOrder->delete();
        return response()->json(['success' => true, 'message' => 'Manufacturing order deleted.']);
    }
}
