<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Warehouses
 * Warehouse management. Business/branch scoped.
 */
class WarehouseController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List warehouses
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam branch_id int Filter by branch.
     * @queryParam status string Filter by status (active|inactive).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Warehouse::with(['business', 'branch'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('business_id'), function ($q) use ($request, $user) {
                $this->authService->assertBusinessAccess($user, (int) $request->business_id);
                $q->where('business_id', $request->business_id);
            })
            ->when($request->filled('branch_id'), fn($q) =>
                $q->where('branch_id', $request->branch_id)
            )
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('name');

        return $this->paginated(WarehouseResource::collection($query->paginate(50)));
    }

    /**
     * Create warehouse
     *
     * @authenticated
     * @bodyParam business_id int required
     * @bodyParam branch_id int required
     * @bodyParam name string required
     * @bodyParam code string optional
     * @bodyParam status string optional (active|inactive)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'branch_id'   => 'required|integer|exists:branches,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50|unique:warehouses,code',
            'status'      => 'nullable|in:active,inactive',
        ]);

        // Verify user has access to the business AND the branch belongs to that business
        $this->authService->assertBusinessAccess($request->user(), (int) $data['business_id']);

        $branch = \App\Models\Branch::findOrFail($data['branch_id']);
        if ($branch->business_id !== (int) $data['business_id']) {
            return $this->error('Branch does not belong to the specified business.', 422);
        }

        $warehouse = Warehouse::create($data);
        AuditService::logCreate('App\\Models\\Warehouse', $warehouse, $warehouse->business_id);

        return $this->created(new WarehouseResource($warehouse->load(['business', 'branch'])));
    }

    /**
     * Get warehouse
     *
     * @authenticated
     * @urlParam warehouse int required The warehouse ID.
     */
    public function show(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $warehouse->business_id);

        return $this->ok(new WarehouseResource($warehouse->load(['business', 'branch'])));
    }

    /**
     * Update warehouse
     *
     * @authenticated
     * @urlParam warehouse int required The warehouse ID.
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $warehouse->business_id);

        $data = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'code'   => "nullable|string|max:50|unique:warehouses,code,{$warehouse->id}",
            'status' => 'nullable|in:active,inactive',
        ]);

        $original = $warehouse->toArray();
        $warehouse->update($data);
        AuditService::logUpdate('App\\Models\\Warehouse', $warehouse, $original, $warehouse->business_id);

        return $this->ok(new WarehouseResource($warehouse->load(['business', 'branch'])));
    }

    /**
     * Delete warehouse
     *
     * @authenticated
     * @urlParam warehouse int required The warehouse ID.
     */
    public function destroy(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $warehouse->business_id);

        AuditService::logDelete('App\\Models\\Warehouse', $warehouse, $warehouse->business_id);
        $warehouse->delete();

        return $this->ok(null, 'Warehouse deleted.');
    }
}
