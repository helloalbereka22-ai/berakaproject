<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public string $emailError = '';

    public function mount()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->roles()->whereIn('name', ['Super Admin', 'Admin', 'Business Manager'])->exists()) {
                return redirect()->route('admin.dashboard');
            }
            
            if ($user->roles()->whereIn('name', [
                'Manufacture', 'Manufacturing Manager', 'Factory Supervisor',
                'Manufacturing Staff', 'Warehouse Staff',
            ])->exists()) {
                return redirect()->route('admin.manufacturing.dashboard');
            }
            
            if ($user->roles()->whereIn('name', ['Branch Seller', 'Seller', 'Sales Staff'])->exists()) {
                return redirect()->route('admin.branch-seller.dashboard');
            }
            
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            
            return redirect()->route('home');
        }
    }

    public function login(?string $email = null, ?string $password = null, ?bool $remember = null): mixed
    {
        $email = $email ?? $this->email;
        $password = $password ?? $this->password;
        $remember = $remember ?? $this->remember;

        $this->emailError = '';

        // Basic validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->emailError = 'Please enter a valid email address.';
            return null;
        }

        if (empty($password)) {
            $this->emailError = 'Password is required.';
            return null;
        }

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Role-based routing
            if ($user->roles()->whereIn('name', ['Super Admin', 'Admin', 'Business Manager'])->exists()) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->roles()->whereIn('name', [
                'Manufacture', 'Manufacturing Manager', 'Factory Supervisor',
                'Manufacturing Staff', 'Warehouse Staff',
            ])->exists() || $user->roles()->where('name', 'like', '%Manufactur%')->exists()) {
                return redirect()->route('admin.manufacturing.dashboard');
            }

            if ($user->roles()->whereIn('name', ['Branch Seller', 'Seller', 'Sales Staff'])->exists()) {
                return redirect()->route('admin.branch-seller.dashboard');
            }

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            // Customer / All other roles → home
            return redirect()->route('home');
        }

        \Illuminate\Support\Facades\Log::error('LOGIN_FAILED', ['email' => $email, 'password_length' => strlen($password)]);

        $this->emailError = 'These credentials do not match our records.';
        return null;
    }

    public function render()
    {
        return view('livewire.public.login');
    }
}
