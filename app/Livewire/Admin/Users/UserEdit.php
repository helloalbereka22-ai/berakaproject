<?php

namespace App\Livewire\Admin\Users;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class UserEdit extends Component
{
    public User   $editUser;
    public string $name       = '';
    public string $email      = '';
    public string $password   = '';
    public string $status     = 'active';

    // Role assignment
    public array  $userRoles  = [];
    public string $newRoleId  = '';
    public string $newBusinessId = '';
    public string $newBranchId  = '';

    public array $roles      = [];
    public array $businesses = [];
    public array $branches   = [];

    public function mount(int $id): void
    {
        $this->authorize('user.update');
        $this->editUser  = User::with('roles')->findOrFail($id);

        $authUser = auth()->user();
        if (!$authUser->isSuperAdmin()) {
            // Ensure editing user is within scope
            $sharedBusiness = collect($this->editUser->assignedBusinessIds())
                ->intersect($authUser->assignedBusinessIds());
            if ($sharedBusiness->isEmpty() && !$this->editUser->isSuperAdmin()) {
                abort(403);
            }
        }

        $this->name     = $this->editUser->name;
        $this->email    = $this->editUser->email;
        $this->status   = $this->editUser->status ?? 'active';
        $this->userRoles = $this->editUser->roles->map(fn($r) => [
            'pivot_id'    => $r->pivot->id,
            'role_name'   => $r->name,
            'business_id' => $r->pivot->business_id,
            'branch_id'   => $r->pivot->branch_id,
        ])->toArray();

        $this->roles = Role::orderBy('name')->get(['id', 'name'])->toArray();
        $this->businesses = $authUser->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $authUser->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedNewBusinessId(): void
    {
        $this->branches = $this->newBusinessId
            ? Branch::where('business_id', $this->newBusinessId)->where('status', 'active')
                ->orderBy('name')->get(['id', 'name'])->toArray()
            : [];
        $this->newBranchId = '';
    }

    public function addRole(): void
    {
        $this->authorize('role.update');
        $this->validate([
            'newRoleId'     => 'required|integer|exists:roles,id',
            'newBusinessId' => 'nullable|integer|exists:businesses,id',
            'newBranchId'   => 'nullable|integer|exists:branches,id',
        ]);

        $exists = $this->editUser->roles()
            ->where('role_id', $this->newRoleId)
            ->wherePivot('business_id', $this->newBusinessId ?: null)
            ->wherePivot('branch_id', $this->newBranchId ?: null)
            ->exists();

        if (!$exists) {
            $this->editUser->roles()->attach($this->newRoleId, [
                'business_id' => $this->newBusinessId ?: null,
                'branch_id'   => $this->newBranchId ?: null,
            ]);
            $this->editUser->flushPermissionCache();
            AuditService::log('role_assigned', 'App\\Models\\User', $this->editUser->id,
                [], ['role_id' => $this->newRoleId, 'business_id' => $this->newBusinessId],
                $this->newBusinessId ?: null
            );
        }

        $this->editUser->load('roles');
        $this->userRoles = $this->editUser->roles->map(fn($r) => [
            'pivot_id'    => $r->pivot->id,
            'role_name'   => $r->name,
            'business_id' => $r->pivot->business_id,
            'branch_id'   => $r->pivot->branch_id,
        ])->toArray();

        $this->reset(['newRoleId', 'newBusinessId', 'newBranchId']);
        session()->flash('success', 'Role assigned.');
    }

    public function removeRole(int $roleId, ?int $businessId, ?int $branchId): void
    {
        $this->authorize('role.update');
        $this->editUser->roles()
            ->wherePivot('role_id', $roleId)
            ->wherePivot('business_id', $businessId)
            ->wherePivot('branch_id', $branchId)
            ->detach($roleId);

        $this->editUser->flushPermissionCache();
        $this->editUser->load('roles');
        $this->userRoles = $this->editUser->roles->map(fn($r) => [
            'pivot_id'    => $r->pivot->id,
            'role_name'   => $r->name,
            'business_id' => $r->pivot->business_id,
            'branch_id'   => $r->pivot->branch_id,
        ])->toArray();

        session()->flash('success', 'Role removed.');
    }

    public function save(): void
    {
        $this->authorize('user.update');
        $rules = [
            'name'   => 'required|string|max:255',
            'email'  => "required|email|unique:users,email,{$this->editUser->id}",
            'status' => 'required|in:active,inactive',
        ];
        if ($this->password) {
            $rules['password'] = 'string|min:8';
        }
        $data     = $this->validate($rules);
        $original = $this->editUser->toArray();

        $updateData = ['name' => $data['name'], 'email' => $data['email'], 'status' => $data['status']];
        if ($this->password) {
            $updateData['password'] = Hash::make($this->password);
        }

        $this->editUser->update($updateData);
        $this->editUser->flushPermissionCache();
        AuditService::logUpdate('App\\Models\\User', $this->editUser, $original);

        session()->flash('success', 'User updated.');
        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.edit')->title('Edit User');
    }
}
