<?php

namespace App\Livewire\Store;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class CustomerRegister extends Component
{
    public string $name                  = '';
    public string $email                 = '';
    public string $phone                 = '';
    public string $password              = '';
    public string $password_confirmation = '';

    protected array $rules = [
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:customers,email',
        'phone'    => 'nullable|string|max:30',
        'password' => 'required|min:8|confirmed',
    ];

    public function register(): void
    {
        $this->validate();

        $customer = Customer::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone ?: null,
            'password' => Hash::make($this->password),
            'status'   => 'active',
        ]);

        Auth::guard('customer')->login($customer);
        session()->regenerate();

        $this->redirectRoute('store.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.store.customer-register')
            ->title('Create Account — Al-Baraka Store');
    }
}
