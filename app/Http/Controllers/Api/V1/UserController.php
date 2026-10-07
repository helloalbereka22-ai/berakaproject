<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $query = User::with('roles')
            ->when(!$authUser->isSuperAdmin(), fn($q) =>
                $q->whereHas('roles', fn($r) =>
                    $r->whereIn('role_user.business_id', $authUser->assignedBusinessIds())
                )
            )
            // SECURITY FIX (M3): The search orWhere is wrapped in a closure so it
            // cannot break out of the business-scope WHERE clause applied above.
            ->when($request->filled('search'), fn($q) =>
                $q->where(fn($inner) =>
                    $inner->where('name', 'like', "%{$request->search}%")
                          ->orWhere('email', 'like', "%{$request->search}%")
                )
            )
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('name');

        return UserResource::collection($query->paginate(50));
    }

    public function store(Request $request)
    {
        $this->authorize('user.create');
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'status'      => 'nullable|in:active,inactive',
            'role_id'     => 'nullable|integer|exists:roles,id',
            'business_id' => 'nullable|integer|exists:businesses,id',
            'branch_id'   => 'nullable|integer|exists:branches,id',
        ]);

        // SECURITY FIX (H2): Verify the creator actually has access to the
        // business they are trying to assign the new user to.
        if (!empty($data['business_id']) && !$request->user()->isSuperAdmin()) {
            $authService = app(\App\Services\AuthorizationService::class);
            $authService->assertBusinessAccess($request->user(), (int) $data['business_id']);
        }

        // Branch must belong to the same business
        if (!empty($data['branch_id']) && !empty($data['business_id'])) {
            $branch = \App\Models\Branch::findOrFail($data['branch_id']);
            if ($branch->business_id !== (int) $data['business_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'The branch does not belong to the specified business.',
                ], 422);
            }
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'] ?? 'active',
        ]);

        if (!empty($data['role_id'])) {
            $user->roles()->attach($data['role_id'], [
                'business_id' => $data['business_id'] ?? null,
                'branch_id'   => $data['branch_id'] ?? null,
            ]);
            // SECURITY FIX (M10): Flush permission cache immediately after role assignment
            $user->flushPermissionCache();
        }

        AuditService::logCreate('App\\Models\\User', $user, $data['business_id'] ?? null);

        return new UserResource($user->load('roles'));
    }

    public function show(Request $request, User $user)
    {
        $authUser = $request->user();
        if (!$authUser->isSuperAdmin() && $authUser->id !== $user->id) {
            $shared = collect($user->assignedBusinessIds())
                ->intersect($authUser->assignedBusinessIds());
            abort_if($shared->isEmpty(), 403);
        }
        return new UserResource($user->load('roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('user.update');

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ["sometimes", "email", Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'status'   => 'nullable|in:active,inactive',
        ]);

        $original = $user->toArray();
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->flushPermissionCache();
        AuditService::logUpdate('App\\Models\\User', $user, $original);

        return new UserResource($user->load('roles'));
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('user.delete');
        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Cannot delete a Super Admin.'], 422);
        }

        // SECURITY FIX (M10): Flush permission cache before deleting so any
        // cached permissions for this user are immediately invalidated.
        $user->roles()->detach();
        $user->flushPermissionCache();

        AuditService::logDelete('App\\Models\\User', $user);
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
