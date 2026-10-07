<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::with('roles')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orderBy('created_at', 'desc')
            ->take(10) // Limit for widget view, full view would use pagination
            ->get();

        return view('livewire.admin.widgets.user-management', [
            'users' => $users,
        ]);
    }
}
