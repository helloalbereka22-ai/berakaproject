<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <span class="text-gray-600 font-medium">My Account</span>
    </nav>

    {{-- Account nav --}}
    @include('livewire.store.partials.account-nav')

    <h1 class="text-2xl font-extrabold text-gray-900 mb-6">My Account</h1>

    {{-- Success message --}}
    @if($successMessage)
    <div class="mb-6 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        {{ $successMessage }}
    </div>
    @endif

    <div class="space-y-6">

        {{-- Profile details --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="font-bold text-gray-900 text-base mb-5">Profile Information</h2>
            <form wire:submit="saveProfile" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Full Name</label>
                        <input wire:model="name" type="text" required
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:border-indigo-500 outline-none transition
                                   @error('name') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address</label>
                        <input wire:model="email" type="email" required
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:border-indigo-500 outline-none transition
                                   @error('email') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                        @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone</label>
                        <input wire:model="phone" type="tel"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 outline-none transition"
                            placeholder="+251 9 ...">
                        @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveProfile"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition">
                        <span wire:loading.remove wire:target="saveProfile">Save Changes</span>
                        <span wire:loading wire:target="saveProfile">Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Change password --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="font-bold text-gray-900 text-base mb-5">Change Password</h2>
            <form wire:submit="changePassword" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Current Password</label>
                    <input wire:model="currentPassword" type="password"
                        class="w-full px-3 py-2.5 border rounded-xl text-sm focus:border-indigo-500 outline-none transition
                               @error('currentPassword') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                    @error('currentPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">New Password</label>
                        <input wire:model="newPassword" type="password"
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:border-indigo-500 outline-none transition
                                   @error('newPassword') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                            placeholder="At least 8 characters">
                        @error('newPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm New Password</label>
                        <input wire:model="passwordConfirm" type="password"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 outline-none transition">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="changePassword"
                        class="px-5 py-2.5 bg-gray-800 hover:bg-gray-900 text-white font-bold text-sm rounded-xl transition">
                        <span wire:loading.remove wire:target="changePassword">Update Password</span>
                        <span wire:loading wire:target="changePassword">Updating...</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Danger zone --}}
        <div class="bg-white border border-red-100 rounded-2xl p-6">
            <h2 class="font-bold text-gray-900 text-base mb-4">Sign Out</h2>
            <p class="text-sm text-gray-500 mb-4">You will be signed out of your customer account.</p>
            <button wire:click="logout"
                wire:confirm="Sign out of your account?"
                class="px-5 py-2.5 border border-red-300 text-red-600 hover:bg-red-50 font-bold text-sm rounded-xl transition">
                Sign Out
            </button>
        </div>

    </div>
</div>
