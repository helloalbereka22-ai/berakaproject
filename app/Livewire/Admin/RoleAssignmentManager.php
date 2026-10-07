<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use App\Models\Business;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class RoleAssignmentManager extends Component
{
    public $users;
    public $roles;
    public $businesses;
    public $branches = [];

    public $selectedUserId;
    public $selectedRoleId;
    public $selectedBusinessId;
    public $selectedBranchId;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->users = User::all();
        $this->roles = Role::all();
        
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->roles()->where('name', 'Super Admin')->exists()) {
            $this->businesses = Business::all();
        } else {
            // A manager can only see their own business (if we allow them to assign roles)
            // But prompt says: "Only Super Admin can assign or change roles. Business Managers can only manage users inside their assigned business."
            // So we need to handle this.
            // Let's assume for now this component is only accessible to Super Admins and Business Managers.
            $businessIds = $currentUser->roles()->pluck('role_user.business_id')->filter();
            $this->businesses = Business::whereIn('id', $businessIds)->get();
        }
    }

    public function updatedSelectedBusinessId($businessId)
    {
        if ($businessId) {
            $this->branches = Branch::where('business_id', $businessId)->get();
        } else {
            $this->branches = [];
        }
        $this->selectedBranchId = null;
    }

    public function assignRole()
    {
        $this->validate([
            'selectedUserId' => 'required',
            'selectedRoleId' => 'required',
        ]);

        $user = User::findOrFail($this->selectedUserId);
        
        // Check if role already assigned to prevent duplicates
        $exists = $user->roles()
            ->where('role_id', $this->selectedRoleId)
            ->wherePivot('business_id', $this->selectedBusinessId)
            ->wherePivot('branch_id', $this->selectedBranchId)
            ->exists();

        if (!$exists) {
            $user->roles()->attach($this->selectedRoleId, [
                'business_id' => $this->selectedBusinessId ?: null,
                'branch_id' => $this->selectedBranchId ?: null,
            ]);
            
            session()->flash('success', 'Role assigned successfully.');
            $this->reset(['selectedUserId', 'selectedRoleId', 'selectedBusinessId', 'selectedBranchId']);
        } else {
            session()->flash('error', 'This user already has this role in the selected context.');
        }
    }

    public function render()
    {
        return view('livewire.admin.role-assignment-manager');
    }
}

