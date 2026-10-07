<?php

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use App\Models\Business;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BranchIndex extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $businessId = '';
    public string $status     = '';
    public bool   $showDeleteConfirm = false;
    public ?int   $deleteId   = null;

    public array $businesses = [];

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
        $this->authorize('branch.update');
        $branch   = Branch::findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $branch->business_id);
        $original = $branch->toArray();
        $branch->status = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->save();
        AuditService::logUpdate('App\\Models\\Branch', $branch, $original, $branch->business_id);
        session()->flash('success', "Branch '{$branch->name}' status updated.");
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('branch.delete');
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $this->authorize('branch.delete');
        $branch = Branch::findOrFail($this->deleteId);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $branch->business_id);
        AuditService::logDelete('App\\Models\\Branch', $branch, $branch->business_id);
        $branch->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Branch deleted.');
    }

    public function render()
    {
        $user   = auth()->user();
        $query  = Branch::with('business', 'manager')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
            )
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->withCount('locations')
            ->orderBy('business_id')->orderBy('name');

        return view('livewire.admin.branches.index', ['branches' => $query->paginate(20)])
            ->title('Branches');
    }
}
