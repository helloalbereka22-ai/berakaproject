<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Http\Resources\LocationResource;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;

/**
 * InventoryLocation has been replaced by Location (the `locations` table).
 * This controller is a compatibility shim that forwards to Location so
 * existing API consumers continue to work.
 *
 * Consumers should migrate to GET /api/v1/locations.
 */
class InventoryLocationController extends Controller
{
    public function __construct(private readonly AuthorizationService $authService) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Location::query()
            ->with('branch.business')
            ->when($request->filled('branch_id'), fn($q) =>
                $q->where('branch_id', $request->branch_id)
            )
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $branchIds = $user->assignedBranchIds();
                $q->whereIn('branch_id', $branchIds);
            })
            ->orderBy('name');

        return LocationResource::collection($query->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'name'      => 'required|string|max:255',
            'type'      => 'nullable|string|in:warehouse,outlet,raw_material_store',
            'status'    => 'nullable|in:active,inactive',
        ]);

        $this->authService->assertBranchAccess($request->user(), (int) $data['branch_id']);

        $location = Location::create($data);
        return new LocationResource($location);
    }

    public function show(Request $request, int $id)
    {
        $location = Location::with('branch.business')->findOrFail($id);
        $this->authService->assertBranchAccess($request->user(), $location->branch_id);
        return new LocationResource($location);
    }

    public function update(Request $request, int $id)
    {
        $location = Location::findOrFail($id);
        $this->authService->assertBranchAccess($request->user(), $location->branch_id);

        $data = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'type'   => 'nullable|string|in:warehouse,outlet,raw_material_store',
            'status' => 'nullable|in:active,inactive',
        ]);

        $location->update($data);
        return new LocationResource($location->fresh('branch.business'));
    }

    public function destroy(Request $request, int $id)
    {
        $location = Location::findOrFail($id);
        $this->authService->assertBranchAccess($request->user(), $location->branch_id);
        $location->delete();
        return response()->json(['message' => 'Location deleted.']);
    }
}
