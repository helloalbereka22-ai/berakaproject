<?php

namespace App\Livewire\Store;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class CustomerLogin extends Component
{
    public string $email    = '';
    public string $password = '';
    public bool   $remember = false;

    protected array $rules = [
        'email'    => 'required|email',
        'password' => 'required',
    ];

    public function login(): void
    {
        $this->validate();

        // Guard: if customer exists but has no password set, they can't log in via password
        $customer = \App\Models\Customer::where('email', $this->email)->first();
        if ($customer && empty($customer->password)) {
            $this->addError('email', 'This account has no password set. Please register to set one.');
            return;
        }

        if (Auth::guard('customer')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            session()->regenerate();
            $this->redirectIntended(route('store.index'), navigate: true);
            return;
        }

        $this->addError('email', 'These credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.store.customer-login')
            ->title('Sign In — Al-Baraka Store');
    }
}
