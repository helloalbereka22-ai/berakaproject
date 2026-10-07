<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductionBatchResource;
use App\Models\ManufacturingOrder;
use App\Models\ProductionBatch;
use App\Services\AuthorizationService;
use App\Services\ManufacturingService;
use Illuminate\Http\Request;

class ProductionBatchController extends Controller
{
    public function __construct(
        private readonly ManufacturingService $manufacturing,
        private readonly AuthorizationService $authService,
    ) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = ProductionBatch::with(['manufacturingOrder.product', 'supervisor', 'qualityCheck'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('business_id'), fn($q) =>
                $q->where('business_id', $request->business_id)
            )
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->filled('manufacturing_order_id'), fn($q) =>
                $q->where('manufacturing_order_id', $request->manufacturing_order_id)
            )
            ->orderByDesc('started_at');

        return ProductionBatchResource::collection($query->paginate(25));
    }

    /**
     * Open a new batch for a manufacturing order.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'manufacturing_order_id' => 'required|integer|exists:manufacturing_orders,id',
            'planned_quantity'       => 'nullable|numeric|min:0.0001',
            'oil_unit'               => 'nullable|in:ml,litre',
            'supervisor_id'          => 'nullable|integer|exists:users,id',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $order = ManufacturingOrder::findOrFail($data['manufacturing_order_id']);
        $this->authService->assertBusinessAccess($request->user(), $order->business_id);

        $batch = $this->manufacturing->openBatch($order, $data);

        return new ProductionBatchResource($batch->load('manufacturingOrder.product'));
    }

    public function show(Request $request, ProductionBatch $productionBatch)
    {
        if ($productionBatch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $productionBatch->business_id);
        }
        return new ProductionBatchResource(
            $productionBatch->load([
                'manufacturingOrder.product',
                'rawMaterialConsumptions.rawMaterial.category',
                'packingConsumptions.rawMaterial',
                'qualityCheck',
                'finishedGood',
                'supervisor',
            ])
        );
    }

    public function update(Request $request, ProductionBatch $productionBatch)
    {
        if ($productionBatch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $productionBatch->business_id);
        }

        $data = $request->validate([
            'notes'        => 'nullable|string|max:1000',
            'supervisor_id'=> 'nullable|integer|exists:users,id',
        ]);

        // SECURITY FIX (M2): Verify supervisor belongs to the same business
        if (!empty($data['supervisor_id']) && $productionBatch->business_id) {
            $supervisorHasAccess = \App\Models\User::where('id', $data['supervisor_id'])
                ->whereHas('roles', fn($q) =>
                    $q->wherePivot('business_id', $productionBatch->business_id)
                )->exists();

            $isSuperAdmin = \App\Models\User::find($data['supervisor_id'])?->isSuperAdmin();

            if (!$supervisorHasAccess && !$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'The supervisor must be assigned to the same business as the batch.',
                ], 422);
            }
        }

        $productionBatch->update($data);

        return new ProductionBatchResource($productionBatch->fresh());
    }

    public function destroy(Request $request, ProductionBatch $productionBatch)
    {
        // SECURITY FIX (C4): assertBusinessAccess was missing — IDOR vulnerability
        if ($productionBatch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $productionBatch->business_id);
        }

        if (!$request->user()->hasPermission('manufacturing.delete') && !$request->user()->isSuperAdmin()) {
            abort(403, 'You do not have permission to delete production batches.');
        }

        if ($productionBatch->status !== ProductionBatch::STATUS_OPEN) {
            return response()->json(['message' => 'Only open batches can be deleted.'], 422);
        }

        \App\Services\AuditService::logDelete(
            'App\\Models\\ProductionBatch',
            $productionBatch,
            $productionBatch->business_id
        );

        $productionBatch->delete();
        return response()->json(['success' => true, 'message' => 'Batch deleted.']);
    }

    // ── Special actions ───────────────────────────────────────────────────

    /**
     * POST /api/v1/production-batches/{id}/consume-raw-material
     */
    public function consumeRawMaterial(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'product_id'  => 'required|integer|exists:products,id',
            'quantity_kg' => 'required|numeric|min:0.0001',
            'lot_number'  => 'nullable|string|max:100',
        ]);

        try {
            $consumption = $this->manufacturing->consumeRawMaterial(
                $batch,
                $data['location_id'],
                $data['product_id'],
                (float) $data['quantity_kg'],
                $data['lot_number'] ?? null
            );
            return response()->json(['message' => 'Raw material consumed.', 'consumption' => $consumption]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/production-batches/{id}/record-oil-output
     */
    public function recordOilOutput(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        $data = $request->validate([
            'oil_quantity'  => 'required|numeric|min:0.0001',
            'oil_unit'      => 'required|in:ml,litre',
            'location_id'   => 'required|integer|exists:locations,id',
        ]);

        try {
            $batch = $this->manufacturing->recordOilOutput(
                $batch,
                (float) $data['oil_quantity'],
                $data['oil_unit'],
                (int) $data['location_id']
            );
            return new ProductionBatchResource($batch);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/production-batches/{id}/quality-check
     */
    public function submitQualityCheck(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        $data = $request->validate([
            'status'          => 'required|in:approved,rejected',
            'inspection_date' => 'nullable|date',
            'inspector_id'    => 'nullable|integer|exists:users,id',
            'quantity_checked'=> 'nullable|numeric|min:0',
            'quantity_passed' => 'nullable|numeric|min:0',
            'quantity_failed' => 'nullable|numeric|min:0',
            'remarks'         => 'nullable|string|max:1000',
            'rejected_reason' => 'nullable|string|max:1000',
        ]);

        try {
            $qc = $this->manufacturing->submitQualityCheck($batch, $data);
            return response()->json([
                'message' => "Batch QC {$data['status']}.",
                'quality_check' => $qc,
                'batch_status'  => $batch->fresh()->status,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/production-batches/{id}/complete
     */
    public function complete(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        $data = $request->validate([
            'finished_goods_location_id' => 'required|integer|exists:locations,id',
            'selling_price_per_ml'       => 'nullable|numeric|min:0',
            'packing_items'              => 'nullable|array',
            'packing_items.*.location_id'=> 'required|integer|exists:locations,id',
            'packing_items.*.product_id' => 'required|integer|exists:products,id',
            'packing_items.*.quantity'   => 'required|numeric|min:0.0001',
        ]);

        try {
            $batch = $this->manufacturing->completeBatch(
                $batch,
                (int) $data['finished_goods_location_id'],
                (float) ($data['selling_price_per_ml'] ?? 0),
                $data['packing_items'] ?? []
            );
            return new ProductionBatchResource($batch->load('finishedGood'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/production-batches/{id}/traceability
     */
    public function traceability(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }
        return response()->json($this->manufacturing->getTraceability($batch));
    }

    /**
     * POST /api/v1/production-batches/{id}/distribute
     */
    public function distribute(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }
        $data = $request->validate([
            'source_location_id'      => 'required|integer|exists:locations,id',
            'destination_branch_id'   => 'required|integer|exists:branches,id',
            'destination_location_id' => 'required|integer|exists:locations,id',
            'quantity'                => 'required|numeric|min:0.0001',
            'notes'                   => 'nullable|string|max:500',
        ]);

        try {
            $distribution = $this->manufacturing->distribute(
                $batch,
                (int) $data['source_location_id'],
                (int) $data['destination_branch_id'],
                (int) $data['destination_location_id'],
                (float) $data['quantity'],
                $data['notes'] ?? ''
            );
            return response()->json(['message' => 'Distribution recorded.', 'distribution' => $distribution]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/production-batches/{id}/return
     */
    public function recordReturn(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }
        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'quantity'    => 'required|numeric|min:0.0001',
            'reason'      => 'required|string|max:500',
        ]);

        try {
            $return = $this->manufacturing->recordReturn(
                $batch,
                (int) $data['location_id'],
                (float) $data['quantity'],
                $data['reason']
            );
            return response()->json(['message' => 'Return recorded.', 'return' => $return]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/production-batches/{id}/reconcile
     */
    public function reconcile(Request $request, ProductionBatch $batch)
    {
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        $reconciliation = $this->manufacturing->reconcileBatch($batch);

        return response()->json([
            'message'        => $reconciliation->has_mismatch
                ? 'Mismatch detected — investigation required.'
                : 'Reconciliation clean — quantities match.',
            'reconciliation' => $reconciliation,
        ], $reconciliation->has_mismatch ? 422 : 200);
    }

    /**
     * POST /api/v1/production-batches/compare
     * SECURITY FIX (H3): Validate all requested batch IDs belong to businesses
     * the authenticated user can access before returning comparison data.
     */
    public function compare(Request $request)
    {
        $data = $request->validate([
            'batch_ids'   => 'required|array|min:2|max:10',
            'batch_ids.*' => 'integer|exists:production_batches,id',
        ]);

        $user    = $request->user();
        $batches = \App\Models\ProductionBatch::whereIn('id', $data['batch_ids'])->get();

        foreach ($batches as $batch) {
            if ($batch->business_id) {
                $this->authService->assertBusinessAccess($user, $batch->business_id);
            } elseif (!$user->isSuperAdmin()) {
                abort(403, "Batch #{$batch->id} is not accessible.");
            }
        }

        return response()->json($this->manufacturing->compareBatches($data['batch_ids']));
    }

    /**
     * GET /api/v1/production-batches/yield-report
     */
    public function yieldReport(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'from'        => 'nullable|date',
            'to'          => 'nullable|date',
        ]);

        $this->authService->assertBusinessAccess($request->user(), (int) $data['business_id']);

        return response()->json(
            $this->manufacturing->getYieldReportByCategory(
                (int) $data['business_id'],
                $data['from'] ?? null,
                $data['to']   ?? null
            )
        );
    }
}
