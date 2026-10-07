<?php

namespace App\Livewire\Admin\Users;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class UserCreate extends Component
{
    public string $name       = '';
    public string $email      = '';
    public string $password   = '';
    public string $status     = 'active';
    public string $roleId     = '';
    public string $businessId = '';
    public string $branchId   = '';

    public array $roles      = [];
    public array $businesses = [];
    public array $branches   = [];

    public function mount(): void
    {
        $this->authorize('user.create');
        $authUser = auth()->user();

        $this->roles = Role::orderBy('name')->get(['id', 'name'])->toArray();
        $this->businesses = $authUser->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $authUser->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedBusinessId(): void
    {
        $this->branches = $this->businessId
            ? Branch::where('business_id', $this->businessId)->where('status', 'active')
                ->orderBy('name')->get(['id', 'name'])->toArray()
            : [];
        $this->branchId = '';
    }

    protected function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'status'     => 'required|in:active,inactive',
            'roleId'     => 'required|integer|exists:roles,id',
            'businessId' => 'nullable|integer|exists:businesses,id',
            'branchId'   => 'nullable|integer|exists:branches,id',
        ];
    }

    public function save(): void
    {
        $this->authorize('user.create');
        $data = $this->validate();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'],
        ]);

        $user->roles()->attach($data['roleId'], [
            'business_id' => $data['businessId'] ?: null,
            'branch_id'   => $data['branchId'] ?: null,
        ]);

        AuditService::logCreate('App\\Models\\User', $user, $data['businessId'] ?: null);

        session()->flash('success', "User '{$user->name}' created.");
        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.create')->title('Create User');
    }
}
