<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Password;
use App\Models\User;

#[Layout('components.layouts.guest')]
class ForgotPassword extends Component
{
    public $email = '';
    public $statusMessage = '';
    public $resetToken = '';
    public $resetEmail = '';

    public function sendResetLink()
    {
        $validated = $this->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'No account found with this email address.',
        ]);

        try {
            // Generate reset token
            $status = Password::sendResetLink(['email' => $this->email]);

            if ($status === Password::RESET_LINK_SENT) {
                // Get the actual reset token for display
                $token = Password::broker()->createToken(User::where('email', $this->email)->first());
                $this->resetToken = $token;
                $this->resetEmail = $this->email;
                $this->statusMessage = '✅ Password reset link generated! Copy the link below to reset your password.';
                $this->email = '';
            } else {
                $this->addError('email', 'Failed to generate reset link. Please try again.');
            }
        } catch (\Exception $e) {
            $this->addError('email', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.public.forgot-password');
    }
}

