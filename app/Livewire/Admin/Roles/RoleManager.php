<?php

namespace App\Livewire\Admin\Roles;

use App\Models\Permission;
use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class RoleManager extends Component
{
    // ── Create form ───────────────────────────────────────────────────────
    public string $newName        = '';
    public string $newDescription = '';
    public array  $newPermissions = [];

    // ── Edit modal ────────────────────────────────────────────────────────
    public bool   $showEditModal     = false;
    public ?int   $editId            = null;
    public string $editName          = '';
    public string $editDescription   = '';
    public array  $editPermissions   = [];

    // ── Delete modal ──────────────────────────────────────────────────────
    public bool   $showDeleteModal   = false;
    public ?int   $deleteId          = null;
    public string $deleteName        = '';

    // ── Messages ─────────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage   = '';

    // ── Create ────────────────────────────────────────────────────────────
    public function createRole(): void
    {
        $this->validate([
            'newName' => 'required|string|max:100|unique:roles,name',
        ], ['newName.unique' => 'A role with this name already exists.']);

        $role = Role::create([
            'name'        => trim($this->newName),
            'description' => trim($this->newDescription),
        ]);

        if (!empty($this->newPermissions)) {
            $role->permissions()->sync($this->newPermissions);
            $role->flushPermissionCache();
        }

        $this->newName        = '';
        $this->newDescription = '';
        $this->newPermissions = [];
        $this->successMessage = "Role \"{$role->name}\" created.";
        $this->errorMessage   = '';
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    public function openEdit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->editId          = $id;
        $this->editName        = $role->name;
        $this->editDescription = $role->description ?? '';
        $this->editPermissions = $role->permissions->pluck('id')->map(fn($v) => (string) $v)->toArray();
        $this->showEditModal   = true;
        $this->successMessage  = '';
        $this->errorMessage    = '';
    }

    public function updateRole(): void
    {
        $this->validate([
            'editName' => "required|string|max:100|unique:roles,name,{$this->editId}",
        ], ['editName.unique' => 'A role with this name already exists.']);

        $role = Role::findOrFail($this->editId);

        // Protect system roles from rename
        if (in_array($role->name, ['Super Admin', 'Admin']) && $this->editName !== $role->name) {
            $this->errorMessage = "System role \"{$role->name}\" cannot be renamed.";
            return;
        }

        $role->update([
            'name'        => trim($this->editName),
            'description' => trim($this->editDescription),
        ]);

        $role->permissions()->sync($this->editPermissions);
        $role->flushPermissionCache();

        $this->showEditModal  = false;
        $this->successMessage = "Role \"{$role->name}\" updated.";
        $this->errorMessage   = '';
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editId        = null;
        $this->resetValidation();
    }

    // ── Delete ────────────────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            $this->errorMessage = "System role \"{$role->name}\" cannot be deleted.";
            return;
        }

        $this->deleteId        = $id;
        $this->deleteName      = $role->name;
        $this->showDeleteModal = true;
    }

    public function deleteRole(): void
    {
        $role = Role::findOrFail($this->deleteId);

        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            $this->errorMessage = "System role cannot be deleted.";
            $this->showDeleteModal = false;
            return;
        }

        // Detach permissions and users first
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        $this->showDeleteModal = false;
        $this->deleteId        = null;
        $this->successMessage  = "Role \"{$this->deleteName}\" deleted.";
        $this->deleteName      = '';
        $this->errorMessage    = '';
    }

    public function closeDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId        = null;
        $this->deleteName      = '';
    }

    public function render()
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        // Group permissions by their group label
        $permissionGroups = $permissions->groupBy('group');

        return view('livewire.admin.roles.manager', [
            'roles'            => $roles,
            'permissionGroups' => $permissionGroups,
        ])->title('Role Management');
    }
}
