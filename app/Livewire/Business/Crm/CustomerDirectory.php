<?php

namespace App\Livewire\Business\Crm;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Customer;
use App\Services\CustomerService;

#[Layout('components.layouts.app')]
class CustomerDirectory extends Component
{
    use WithPagination;

    public $search = '';
    public $businessId;

    // Modal state for adding new customer
    public $showAddModal = false;
    public $newName = '';
    public $newEmail = '';
    public $newPhone = '';
    public $newAddress = '';

    protected $rules = [
        'newName' => 'required|string|max:255',
        'newEmail' => 'nullable|email|max:255',
        'newPhone' => 'nullable|string|max:20',
        'newAddress' => 'nullable|string|max:1000',
    ];

    public function mount()
    {
        // For now, assuming the business manager is logged in and we can get their primary business.
        // In a real app, you'd fetch this from the user's role/session.
        $this->businessId = auth()->user()->roles()->first()->pivot->business_id ?? 1;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function saveCustomer(CustomerService $service)
    {
        $this->validate();

        $service->createCustomer($this->businessId, [
            'name' => $this->newName,
            'email' => $this->newEmail,
            'phone' => $this->newPhone,
            'address' => $this->newAddress,
        ]);

        $this->reset(['newName', 'newEmail', 'newPhone', 'newAddress', 'showAddModal']);
        session()->flash('success', 'Customer added successfully.');
    }

    public function render()
    {
        $customers = Customer::where('business_id', $this->businessId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.business.crm.customer-directory', [
            'customers' => $customers,
        ]);
    }
}
