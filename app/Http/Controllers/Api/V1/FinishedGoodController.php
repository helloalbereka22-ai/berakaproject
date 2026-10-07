<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\FinishedGoodResource;
use App\Models\FinishedGood;
use App\Models\ProductionBatch;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Finished Goods
 * Finished goods are created after QC approval. Read-only via this endpoint.
 */
class FinishedGoodController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List finished goods
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam batch_id int Filter by production batch.
     * @queryParam product_id int Filter by product.
     * @queryParam status string Filter by status (available|distributed|reserved).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = FinishedGood::with(['batch', 'product.category'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('batch', fn($bq) =>
                    $bq->whereIn('business_id', $businessIds)
                );
            })
            ->when($request->filled('business_id'), function ($q) use ($request, $user) {
                $this->authService->assertBusinessAccess($user, (int) $request->business_id);
                $q->whereHas('batch', fn($bq) =>
                    $bq->where('business_id', $request->business_id)
                );
            })
            ->when($request->filled('batch_id'),   fn($q) => $q->where('production_batch_id', $request->batch_id))
            ->when($request->filled('product_id'), fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('status'),     fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(FinishedGoodResource::collection($query->paginate($perPage)));
    }

    /**
     * Get finished good
     *
     * @authenticated
     * @urlParam finished_good int required The finished good ID.
     */
    public function show(Request $request, FinishedGood $finishedGood): JsonResponse
    {
        $batchBusinessId = $finishedGood->batch?->business_id;
        if ($batchBusinessId) {
            $this->authService->assertBusinessAccess($request->user(), $batchBusinessId);
        }

        return $this->ok(new FinishedGoodResource(
            $finishedGood->load(['batch.manufacturingOrder.product', 'product.category'])
        ));
    }
}
