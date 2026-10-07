<?php

namespace App\Livewire\Store;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.marketplace')]
class AccountProfile extends Component
{
    public string $name              = '';
    public string $email             = '';
    public string $phone             = '';
    public string $currentPassword   = '';
    public string $newPassword       = '';
    public string $passwordConfirm   = '';
    public string $successMessage    = '';

    public function mount(): void
    {
        if (!auth('customer')->check()) {
            $this->redirectRoute('customer.login');
            return;
        }

        $customer      = auth('customer')->user();
        $this->name    = $customer->name;
        $this->email   = $customer->email ?? '';
        $this->phone   = $customer->phone ?? '';
    }

    public function saveProfile(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . auth('customer')->id(),
            'phone' => 'nullable|string|max:30',
        ]);

        $customer = auth('customer')->user();
        $customer->update([
            'name'  => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ]);

        $this->successMessage = 'Profile updated successfully.';
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword'     => 'required|min:8|confirmed:passwordConfirm',
        ]);

        $customer = auth('customer')->user();

        if (!Hash::check($this->currentPassword, $customer->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $customer->update(['password' => Hash::make($this->newPassword)]);

        $this->currentPassword = '';
        $this->newPassword     = '';
        $this->passwordConfirm = '';
        $this->successMessage  = 'Password changed successfully.';
    }

    public function logout(): void
    {
        Auth::guard('customer')->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirectRoute('store.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.store.account-profile')
            ->title('My Account — Al-Baraka Store');
    }
}
