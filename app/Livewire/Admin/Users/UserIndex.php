<?php

namespace App\Livewire\Admin\Users;

use App\Models\Business;
use App\Models\User;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class UserIndex extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $businessId = '';
    public string $status     = '';
    public bool   $showDeleteConfirm = false;
    public ?int   $deleteId   = null;
    public array  $businesses = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedBusinessId(): void { $this->resetPage(); }
    public function updatedStatus(): void     { $this->resetPage(); }

    public function toggleStatus(int $id): void
    {
        $this->authorize('user.update');
        $u = User::findOrFail($id);
        if ($u->isSuperAdmin() && auth()->id() !== $u->id) {
            session()->flash('error', 'Cannot change Super Admin status.');
            return;
        }
        $original = $u->toArray();
        $u->status = $u->status === 'active' ? 'inactive' : 'active';
        $u->save();
        AuditService::logUpdate('App\\Models\\User', $u, $original);
        session()->flash('success', "User status updated.");
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('user.delete');
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $this->authorize('user.delete');
        $u = User::findOrFail($this->deleteId);
        if ($u->isSuperAdmin() || $u->hasRole('Admin')) {
            session()->flash('error', 'Cannot delete a Super Admin or Administrator.');
            $this->showDeleteConfirm = false;
            return;
        }
        AuditService::logDelete('App\\Models\\User', $u);
        $u->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'User deleted.');
    }

    public function render()
    {
        $authUser = auth()->user();
        $query = User::with([
                'roles' => fn($q) => $q->withPivot('business_id', 'branch_id'),
            ])
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->businessId, fn($q) =>
                $q->whereHas('roles', fn($r) => $r->where('role_user.business_id', $this->businessId))
            )
            ->when(!$authUser->isSuperAdmin(), fn($q) =>
                $q->whereHas('roles', fn($r) =>
                    $r->whereIn('role_user.business_id', $authUser->assignedBusinessIds())
                )
            )
            ->orderBy('name');

        $users = $query->paginate(20);

        // Collect all unique business_id and branch_id values from pivot
        $businessIds = $users->flatMap(fn($u) => $u->roles->pluck('pivot.business_id'))->filter()->unique();
        $branchIds   = $users->flatMap(fn($u) => $u->roles->pluck('pivot.branch_id'))->filter()->unique();

        $businessMap = \App\Models\Business::whereIn('id', $businessIds)->pluck('name', 'id');
        $branchMap   = \App\Models\Branch::whereIn('id', $branchIds)->pluck('name', 'id');

        return view('livewire.admin.users.index', [
            'users'       => $users,
            'businessMap' => $businessMap,
            'branchMap'   => $branchMap,
        ])->title('Users');
    }
}
