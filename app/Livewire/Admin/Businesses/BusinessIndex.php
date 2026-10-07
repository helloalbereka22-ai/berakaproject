<?php

namespace App\Livewire\Admin\Businesses;

use App\Models\Business;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class BusinessIndex extends Component
{
    use WithPagination;

    public string $search  = '';
    public string $status  = '';
    public bool   $showDeleteConfirm = false;
    public ?int   $deleteId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void  { $this->resetPage(); }

    public function toggleStatus(int $id): void
    {
        $this->authorize('business.update');
        $business = Business::findOrFail($id);
        $original = $business->toArray();
        $business->status = $business->status === 'active' ? 'inactive' : 'active';
        $business->save();
        AuditService::logUpdate('App\\Models\\Business', $business, $original);
        session()->flash('success', "Business '{$business->name}' status updated.");
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('business.delete');
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $this->authorize('business.delete');
        $business = Business::findOrFail($this->deleteId);
        AuditService::logDelete('App\\Models\\Business', $business);
        $business->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Business deleted.');
    }

    public function render()
    {
        $businesses = Business::withTrashed(false)
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->withCount('branches')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.businesses.index', compact('businesses'))
            ->title('Businesses');
    }
}
