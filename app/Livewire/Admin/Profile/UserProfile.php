<?php

namespace App\Livewire\Admin\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class UserProfile extends Component
{
    use WithFileUploads;

    // ── Profile fields ────────────────────────────────────────────
    public string $name  = '';
    public string $email = '';
    public $profilePicture = null;

    // ── Password fields ───────────────────────────────────────────
    public string $current_password    = '';
    public string $new_password        = '';
    public string $new_password_confirmation = '';

    // ── UI state ──────────────────────────────────────────────────
    public bool   $profileSaved  = false;
    public bool   $passwordSaved = false;
    public string $passwordError = '';
    public string $pictureError  = '';

    public function mount(): void
    {
        $user        = Auth::user();
        $this->name  = $user->name;
        $this->email = $user->email;
    }

    // ── Upload profile picture ─────────────────────────────────────
    public function uploadProfilePicture(): void
    {
        $this->pictureError = '';

        $this->validate([
            'profilePicture' => 'required|image|mimes:jpeg,png,gif|max:2048',
        ]);

        try {
            $user = Auth::user();

            // Ensure avatars directory exists
            if (!Storage::disk('public')->exists('avatars')) {
                Storage::disk('public')->makeDirectory('avatars', 0755, true);
            }

            // Delete old picture if it exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new picture with a unique name to avoid conflicts
            $filename = time() . '_' . $user->id . '_' . $this->profilePicture->getClientOriginalName();
            $path = $this->profilePicture->storeAs('avatars', $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file on disk');
            }

            // Try to update user record - use DB transaction for safety
            \DB::beginTransaction();
            try {
                $user->profile_picture = $path;
                $user->save();
                \DB::commit();
            } catch (\Exception $dbError) {
                \DB::rollBack();
                // If update fails, delete the stored file
                Storage::disk('public')->delete($path);
                throw new \Exception('Failed to save profile picture to user account: ' . $dbError->getMessage());
            }

            // Reset the input
            $this->profilePicture = null;

            // Dispatch success event
            $this->dispatch('picture-saved');
        } catch (\Exception $e) {
            $this->pictureError = $e->getMessage() ?: 'Failed to upload profile picture. Please try again.';
            \Log::error('Profile picture upload error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    // ── Remove profile picture ─────────────────────────────────────
    public function removeProfilePicture(): void
    {
        $user = Auth::user();

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update(['profile_picture' => null]);

        $this->dispatch('picture-removed');
    }

    // ── Save profile info ─────────────────────────────────────────
    public function saveProfile(): void
    {
        $this->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name'  => trim($this->name),
            'email' => trim($this->email),
        ]);

        $this->profileSaved = true;
        $this->dispatch('profile-saved');
    }

    // ── Change password ───────────────────────────────────────────
    public function changePassword(): void
    {
        $this->passwordError = '';
        $this->passwordSaved = false;

        $this->validate([
            'current_password'         => 'required',
            'new_password'             => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($this->current_password, Auth::user()->password)) {
            $this->passwordError = 'Current password is incorrect.';
            return;
        }

        Auth::user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->current_password         = '';
        $this->new_password             = '';
        $this->new_password_confirmation = '';
        $this->passwordSaved = true;
        $this->dispatch('password-saved');
    }

    public function render()
    {
        $user = Auth::user();

        // Collect roles with their business/branch context
        $roles = $user->roles->map(function ($role) {
            $business = $role->pivot->business_id
                ? \App\Models\Business::find($role->pivot->business_id)?->name
                : null;
            $branch   = $role->pivot->branch_id
                ? \App\Models\Branch::find($role->pivot->branch_id)?->name
                : null;
            return [
                'name'     => $role->name,
                'business' => $business,
                'branch'   => $branch,
            ];
        });

        return view('livewire.admin.profile.user-profile', [
            'user'  => $user,
            'roles' => $roles,
        ])->title('My Profile');
    }
}
