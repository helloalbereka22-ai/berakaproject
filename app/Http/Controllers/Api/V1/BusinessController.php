<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Businesses
 * Business entity management. Super Admins can create/delete; managers can view/update their own.
 */
class BusinessController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List businesses
     *
     * @authenticated
     * @queryParam status string Filter by status (active|inactive).
     * @queryParam search string Search by name or code.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Business::withCount('branches')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('id', $user->assignedBusinessIds())
            )
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
            )
            ->orderBy('name');

        return $this->paginated(BusinessResource::collection($query->paginate(25)));
    }

    /**
     * Create business
     *
     * Super Admins only.
     *
     * @authenticated
     * @bodyParam name string required
     * @bodyParam code string required Unique identifier code.
     * @bodyParam description string optional
     * @bodyParam status string optional (active|inactive).
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return $this->forbidden('Only Super Administrators can create businesses.');
        }

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:businesses,code',
            'description' => 'nullable|string|max:1000',
            'status'      => 'nullable|in:active,inactive',
        ]);

        $business = Business::create($data);
        AuditService::logCreate('App\\Models\\Business', $business);

        return $this->created(new BusinessResource($business));
    }

    /**
     * Get business
     *
     * @authenticated
     * @urlParam business int required The business ID.
     */
    public function show(Request $request, Business $business): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $business->id);

        return $this->ok(new BusinessResource($business->load('branches')));
    }

    /**
     * Update business
     *
     * @authenticated
     * @urlParam business int required The business ID.
     */
    public function update(Request $request, Business $business): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $business->id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'code'        => "sometimes|string|max:50|unique:businesses,code,{$business->id}",
            'description' => 'nullable|string|max:1000',
            'status'      => 'nullable|in:active,inactive',
        ]);

        $original = $business->toArray();
        $business->update($data);
        AuditService::logUpdate('App\\Models\\Business', $business, $original);

        return $this->ok(new BusinessResource($business));
    }

    /**
     * Delete business
     *
     * Super Admins only.
     *
     * @authenticated
     * @urlParam business int required The business ID.
     */
    public function destroy(Request $request, Business $business): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return $this->forbidden('Only Super Administrators can delete businesses.');
        }

        AuditService::logDelete('App\\Models\\Business', $business);
        $business->delete();

        return $this->ok(null, 'Business deleted.');
    }
}
