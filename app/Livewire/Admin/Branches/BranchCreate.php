<?php

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BranchCreate extends Component
{
    public string  $businessId  = '';
    public string  $name        = '';
    public string  $code        = '';
    public string  $description = '';
    public string  $status      = 'active';
    public string  $managerId   = '';

    public array   $businesses  = [];
    public array   $managers    = [];

    public function mount(): void
    {
        $this->authorize('branch.create');
        $user = auth()->user();

        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name', 'code'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name', 'code'])->toArray();

        // Default to first business if available
        if (count($this->businesses) === 1) {
            $this->businessId = (string) $this->businesses[0]['id'];
            $this->updatedBusinessId();
        }
    }

    public function updatedBusinessId(): void
    {
        $this->managerId = '';
        if ($this->businessId) {
            $selectedBiz = collect($this->businesses)->firstWhere('id', $this->businessId);
            $prefix = ($selectedBiz['code'] ?? '') === 'OIL-FACTORY' ? 'OIL-BR' : 'BOOK-BR';
            
            // Suggest auto-generated code if empty
            if (empty($this->code)) {
                $count = Branch::where('business_id', $this->businessId)->count() + 1;
                $this->code = $prefix . $count;
            }

            // Load staff/managers available for this business
            $businessId = $this->businessId;
            $this->managers = User::whereHas('roles', function ($q) use ($businessId) {
                $q->where('role_user.business_id', $businessId);
            })->orderBy('name')->get(['id', 'name', 'email'])->toArray();
        } else {
            $this->managers = [];
        }
    }

    protected function rules(): array
    {
        return [
            'businessId'  => 'required|integer|exists:businesses,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:branches,code',
            'description' => 'nullable|string|max:1000',
            'status'      => 'required|in:active,inactive',
            'managerId'   => 'nullable|integer|exists:users,id',
        ];
    }

    public function save(): void
    {
        $this->authorize('branch.create');
        $data = $this->validate();

        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->businessId);

        DB::transaction(function () use ($data) {
            $branch = Branch::create([
                'business_id' => $data['businessId'],
                'name'        => trim($data['name']),
                'code'        => strtoupper(trim($data['code'])),
                'description' => $data['description'] ?? null,
                'status'      => $data['status'],
                'manager_id'  => $data['managerId'] ?: null,
            ]);

            // Automatically create default inventory storage warehouse for this branch
            Warehouse::firstOrCreate(
                [
                    'business_id' => $branch->business_id,
                    'branch_id'   => $branch->id,
                    'name'        => $branch->name . ' Stock',
                ],
                [
                    'type'        => 'warehouse',
                    'status'      => 'active',
                ]
            );

            AuditService::logCreate('App\\Models\\Branch', $branch, $branch->business_id);
        });

        session()->flash('success', "Branch '{$this->name}' successfully created with dedicated storage location.");
        $this->redirect(route('admin.branches.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.branches.create')->title('Add New Branch');
    }
}
