<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\DistributionResource;
use App\Models\Distribution;
use App\Models\FinishedGood;
use App\Models\ProductionBatch;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\ManufacturingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Distributions
 * Factory distributions of finished goods to sales points. Business-scoped.
 */
class DistributionController extends ApiController
{
    public function __construct(
        private readonly AuthorizationService  $authService,
        private readonly ManufacturingService  $manufacturing,
    ) {}

    /**
     * List distributions
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam batch_id int Filter by production batch.
     * @queryParam status string Filter by status (pending|in_transit|completed).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Distribution::with(['finishedGood.product', 'destinationBranch', 'creator'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('finishedGood.batch', fn($bq) =>
                    $bq->whereIn('business_id', $businessIds)
                );
            })
            ->when($request->filled('business_id'), function ($q) use ($request, $user) {
                $this->authService->assertBusinessAccess($user, (int) $request->business_id);
                $q->whereHas('finishedGood.batch', fn($bq) =>
                    $bq->where('business_id', $request->business_id)
                );
            })
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->whereHas('finishedGood', fn($fq) =>
                    $fq->where('production_batch_id', $request->batch_id)
                );
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('distributed_at');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(DistributionResource::collection($query->paginate($perPage)));
    }

    /**
     * Get distribution
     *
     * @authenticated
     * @urlParam distribution int required The distribution ID.
     */
    public function show(Request $request, Distribution $distribution): JsonResponse
    {
        $batchBusinessId = $distribution->finishedGood?->batch?->business_id;
        if ($batchBusinessId) {
            $this->authService->assertBusinessAccess($request->user(), $batchBusinessId);
        }

        return $this->ok(new DistributionResource(
            $distribution->load(['finishedGood.batch', 'finishedGood.product', 'destinationBranch', 'creator'])
        ));
    }

    /**
     * Record a distribution
     *
     * @authenticated
     * @bodyParam finished_good_id int required
     * @bodyParam source_location_id int required
     * @bodyParam destination_branch_id int required
     * @bodyParam destination_location_id int required
     * @bodyParam quantity numeric required
     * @bodyParam notes string optional
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'finished_good_id'        => 'required|integer|exists:finished_goods,id',
            'source_location_id'      => 'required|integer|exists:locations,id',
            'destination_branch_id'   => 'required|integer|exists:branches,id',
            'destination_location_id' => 'required|integer|exists:locations,id',
            'quantity'                => 'required|numeric|min:0.0001',
            'notes'                   => 'nullable|string|max:1000',
        ]);

        $fg    = FinishedGood::with('productionBatch')->findOrFail($data['finished_good_id']);
        $batch = $fg->productionBatch;

        if ($batch?->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $batch->business_id);
        }

        try {
            // Route through ManufacturingService so inventory movements are created correctly
            $distribution = $this->manufacturing->distribute(
                $batch,
                (int) $data['source_location_id'],
                (int) $data['destination_branch_id'],
                (int) $data['destination_location_id'],
                (float) $data['quantity'],
                $data['notes'] ?? ''
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(
            new DistributionResource($distribution->load(['finishedGood.product', 'destinationBranch'])),
            'Distribution recorded and inventory movements created.'
        );
    }

    /**
     * Update distribution status
     *
     * @authenticated
     * @urlParam distribution int required The distribution ID.
     * @bodyParam status string (pending|in_transit|completed).
     * @bodyParam quantity_received numeric optional Confirmed received quantity.
     * @bodyParam notes string optional.
     */
    public function update(Request $request, Distribution $distribution): JsonResponse
    {
        $batchBusinessId = $distribution->finishedGood?->batch?->business_id;
        if ($batchBusinessId) {
            $this->authService->assertBusinessAccess($request->user(), $batchBusinessId);
        }

        $data = $request->validate([
            'status'            => 'sometimes|in:pending,in_transit,completed',
            'quantity_received' => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string|max:1000',
        ]);

        if (($data['status'] ?? null) === 'completed') {
            $data['received_at'] = now();
        }

        $original = $distribution->toArray();
        $distribution->update($data);

        AuditService::logUpdate('App\\Models\\Distribution', $distribution, $original, $batchBusinessId);

        return $this->ok(new DistributionResource($distribution->load(['finishedGood.product', 'destinationBranch'])));
    }

    /**
     * Delete distribution
     *
     * @authenticated
     * @urlParam distribution int required The distribution ID.
     */
    public function destroy(Request $request, Distribution $distribution): JsonResponse
    {
        $batchBusinessId = $distribution->finishedGood?->batch?->business_id;
        if ($batchBusinessId) {
            $this->authService->assertBusinessAccess($request->user(), $batchBusinessId);
        }

        if ($distribution->status === 'completed') {
            return $this->error('Completed distributions cannot be deleted.', 409);
        }

        AuditService::logDelete('App\\Models\\Distribution', $distribution, $batchBusinessId);
        $distribution->delete();

        return $this->ok(null, 'Distribution deleted.');
    }
}
