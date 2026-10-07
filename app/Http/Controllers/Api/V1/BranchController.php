<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private readonly AuthorizationService $authService) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Branch::with('business', 'manager')
            ->withCount('locations')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('business_id'), function ($q) use ($request, $user) {
                $this->authService->assertBusinessAccess($user, (int) $request->business_id);
                $q->where('business_id', $request->business_id);
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('business_id')->orderBy('name');

        return BranchResource::collection($query->paginate(50));
    }

    public function store(Request $request)
    {
        $this->authorize('branch.create');
        $data = $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:branches,code',
            'description' => 'nullable|string|max:1000',
            'status'      => 'nullable|in:active,inactive',
        ]);

        $this->authService->assertBusinessAccess($request->user(), (int) $data['business_id']);

        $branch = Branch::create($data);
        AuditService::logCreate('App\\Models\\Branch', $branch, $branch->business_id);

        return new BranchResource($branch->load('business'));
    }

    public function show(Request $request, Branch $branch)
    {
        $this->authService->assertBusinessAccess($request->user(), $branch->business_id);
        return new BranchResource($branch->load(['business', 'manager', 'locations']));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorize('branch.update');
        $this->authService->assertBusinessAccess($request->user(), $branch->business_id);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'code'       => "sometimes|string|max:50|unique:branches,code,{$branch->id}",
            'description'=> 'nullable|string|max:1000',
            'status'     => 'nullable|in:active,inactive',
            'manager_id' => 'nullable|integer|exists:users,id',
        ]);

        $original = $branch->toArray();
        $branch->update($data);
        AuditService::logUpdate('App\\Models\\Branch', $branch, $original, $branch->business_id);

        return new BranchResource($branch->load('business'));
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->authorize('branch.delete');
        $this->authService->assertBusinessAccess($request->user(), $branch->business_id);
        AuditService::logDelete('App\\Models\\Branch', $branch, $branch->business_id);
        $branch->delete();

        return response()->json(['message' => 'Branch deleted.']);
    }
}
