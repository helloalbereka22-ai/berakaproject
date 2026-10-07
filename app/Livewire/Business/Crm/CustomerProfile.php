<?php

namespace App\Livewire\Business\Crm;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Customer;
use App\Services\CustomerService;

#[Layout('components.layouts.app')]
class CustomerProfile extends Component
{
    public $customer;
    public $businessId;

    public function mount($id, CustomerService $service)
    {
        // Get the current user's business context
        $this->businessId = auth()->user()->roles()->first()->pivot->business_id ?? 1;

        // Fetch the customer with relationships
        $this->customer = $service->getCustomerProfile($id);

        // Security check: Ensure this customer belongs to the logged-in manager's business
        if (!$this->customer || $this->customer->business_id !== $this->businessId) {
            abort(404, 'Customer not found or access denied.');
        }
    }

    public function render()
    {
        return view('livewire.business.crm.customer-profile');
    }
}
