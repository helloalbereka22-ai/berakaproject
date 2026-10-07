<?php

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BranchEdit extends Component
{
    public Branch  $branch;
    public string  $name        = '';
    public string  $code        = '';
    public string  $description = '';
    public string  $status      = 'active';
    public string  $managerId   = '';
    public array   $managers    = [];

    public function mount(int $id): void
    {
        $this->authorize('branch.update');
        $this->branch = Branch::with('business')->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $this->branch->business_id);

        $this->name        = $this->branch->name;
        $this->code        = $this->branch->code ?? '';
        $this->description = $this->branch->description ?? '';
        $this->status      = $this->branch->status;
        $this->managerId   = (string) ($this->branch->manager_id ?? '');

        // Users scoped to this branch's business
        $bizId = $this->branch->business_id;
        $this->managers = User::whereHas('roles', function ($q) use ($bizId) {
            $q->where('role_user.business_id', $bizId);
        })->orderBy('name')->get(['id', 'name'])->toArray();
    }

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'code'        => "required|string|max:50|unique:branches,code,{$this->branch->id}",
            'description' => 'nullable|string|max:1000',
            'status'      => 'required|in:active,inactive',
            'managerId'   => 'nullable|integer|exists:users,id',
        ];
    }

    public function save(): void
    {
        $this->authorize('branch.update');
        $data     = $this->validate();
        $original = $this->branch->toArray();

        $this->branch->update([
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'],
            'status'      => $data['status'],
            'manager_id'  => $data['managerId'] ?: null,
        ]);

        AuditService::logUpdate('App\\Models\\Branch', $this->branch, $original, $this->branch->business_id);
        session()->flash('success', 'Branch updated successfully.');
        $this->redirect(route('admin.branches.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.branches.edit')->title('Edit Branch');
    }
}
