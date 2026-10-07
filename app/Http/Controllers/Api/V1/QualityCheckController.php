<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\QualityCheckResource;
use App\Models\ProductionBatch;
use App\Models\QualityCheck;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Quality Control
 * QC inspections on production batches. Business-scoped.
 * A batch cannot enter finished goods until QC is approved.
 */
class QualityCheckController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List QC inspections
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam batch_id int Filter by production batch.
     * @queryParam status string Filter by status (pending|passed|failed).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = QualityCheck::with(['batch.manufacturingOrder', 'inspector'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('batch', fn($bq) =>
                    $bq->whereIn('business_id', $businessIds)
                );
            })
            ->when($request->filled('business_id'), function ($q) use ($request, $user) {
                $this->authService->assertBusinessAccess($user, (int) $request->business_id);
                $q->whereHas('batch', fn($bq) => $bq->where('business_id', $request->business_id));
            })
            ->when($request->filled('batch_id'), fn($q) => $q->where('production_batch_id', $request->batch_id))
            ->when($request->filled('status'),   fn($q) => $q->where('status', $request->status))
            ->orderByDesc('checked_at');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(QualityCheckResource::collection($query->paginate($perPage)));
    }

    /**
     * Get QC record
     *
     * @authenticated
     * @urlParam quality_check int required The QC record ID.
     */
    public function show(Request $request, QualityCheck $qualityCheck): JsonResponse
    {
        $batchBusinessId = $qualityCheck->batch?->business_id;
        if ($batchBusinessId) {
            $this->authService->assertBusinessAccess($request->user(), $batchBusinessId);
        }

        return $this->ok(new QualityCheckResource(
            $qualityCheck->load(['batch.manufacturingOrder.product', 'inspector'])
        ));
    }

    /**
     * Submit QC inspection
     *
     * @authenticated
     * @bodyParam production_batch_id int required
     * @bodyParam status string required (pending|passed|failed).
     * @bodyParam quantity_checked int required
     * @bodyParam quantity_passed int required
     * @bodyParam quantity_failed int required
     * @bodyParam remarks string optional
     * @bodyParam rejected_reason string optional Required when status=failed.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'production_batch_id' => 'required|integer|exists:production_batches,id',
            'status'              => 'required|in:pending,passed,failed',
            'quantity_checked'    => 'required|integer|min:0',
            'quantity_passed'     => 'required|integer|min:0',
            'quantity_failed'     => 'required|integer|min:0',
            'remarks'             => 'nullable|string|max:1000',
            'rejected_reason'     => 'nullable|string|max:1000',
            'inspection_date'     => 'nullable|date',
        ]);

        $batch = ProductionBatch::findOrFail($data['production_batch_id']);
        if ($batch->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        $data['inspected_by']  = $request->user()->id;
        $data['checked_at']    = now();
        $data['inspection_date'] = $data['inspection_date'] ?? now()->toDateString();

        if ($data['status'] === 'passed') {
            $data['approval_date'] = now()->toDateString();
            $batch->update(['status' => 'approved']);
        } elseif ($data['status'] === 'failed') {
            $batch->update(['status' => 'rejected']);
        }

        $qc = QualityCheck::create($data);

        AuditService::log(
            action:        'qc_submitted',
            auditableType: 'App\\Models\\QualityCheck',
            auditableId:   $qc->id,
            afterData:     ['batch' => $batch->batch_number, 'status' => $data['status']],
            businessId:    $batch->business_id,
        );

        return $this->created(new QualityCheckResource($qc->load(['batch', 'inspector'])));
    }

    /**
     * Update QC record
     *
     * @authenticated
     * @urlParam quality_check int required The QC record ID.
     */
    public function update(Request $request, QualityCheck $qualityCheck): JsonResponse
    {
        $batchBusinessId = $qualityCheck->batch?->business_id;
        if ($batchBusinessId) {
            $this->authService->assertBusinessAccess($request->user(), $batchBusinessId);
        }

        $data = $request->validate([
            'remarks'         => 'nullable|string|max:1000',
            'rejected_reason' => 'nullable|string|max:1000',
            'status'          => 'sometimes|in:pending,passed,failed',
        ]);

        $qualityCheck->update($data);

        return $this->ok(new QualityCheckResource($qualityCheck->load(['batch', 'inspector'])));
    }
}
