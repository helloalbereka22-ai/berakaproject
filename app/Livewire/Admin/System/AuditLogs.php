<?php

namespace App\Livewire\Admin\System;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class AuditLogs extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $actionFilter = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedActionFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void     { $this->resetPage(); }
    public function updatedDateTo(): void       { $this->resetPage(); }

    public function render()
    {
        $user  = auth()->user();
        $query = AuditLog::with('user')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
                  ->orWhereNull('business_id')
            )
            ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
            ->when($this->dateFrom, fn($q) => $q->where('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn($q) => $q->where('created_at', '<=', $this->dateTo . ' 23:59:59'))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('description', 'like', "%{$this->search}%")
                        ->orWhere('action', 'like', "%{$this->search}%")
                        ->orWhere('auditable_type', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn($u) =>
                            $u->where('name', 'like', "%{$this->search}%")
                              ->orWhere('email', 'like', "%{$this->search}%")
                        );
                });
            })
            ->orderByDesc('created_at');

        return view('livewire.admin.system.audit-logs', [
            'logs' => $query->paginate(50),
        ])->title('Audit Logs');
    }

    public function delete($id)
    {
        // Find the audit log entry
        $log = AuditLog::findOrFail($id);
        // Optionally add authorization check here
        // $this->authorize('delete', $log);
        $log->delete();
        // Refresh the list (pagination will automatically update)
        session()->flash('message', 'Log deleted successfully.');
    }
}
