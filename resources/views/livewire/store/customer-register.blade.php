<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">

        {{-- Header --}}
        <div class="text-center mb-8">
            <a href="{{ route('store.index') }}" class="inline-flex items-center gap-2 justify-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center">
                    <span class="text-white font-extrabold">A</span>
                </div>
                <span class="text-2xl font-extrabold text-gray-900">Al-Baraka <span class="text-indigo-600">Store</span></span>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
            <p class="text-gray-500 text-sm mt-1">Shop oils and books from both stores in one place</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
            <form wire:submit="register" class="space-y-4">

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input wire:model="name" id="name" type="text" autocomplete="name" required
                        class="w-full px-4 py-2.5 border rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition
                               @error('name') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                        placeholder="Your full name">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input wire:model="email" id="email" type="email" autocomplete="email" required
                        class="w-full px-4 py-2.5 border rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition
                               @error('email') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                        placeholder="you@example.com">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Phone (optional) --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Phone <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input wire:model="phone" id="phone" type="tel" autocomplete="tel"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        placeholder="+251 9 ...">
                    @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        placeholder="At least 8 characters">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                    <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                        placeholder="Repeat your password">
                </div>

                {{-- Submit --}}
                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-70 cursor-not-allowed"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2 mt-2">
                    <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove>Create Account</span>
                    <span wire:loading>Creating...</span>
                </button>
            </form>

            {{-- Login link --}}
            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <a href="{{ route('customer.login') }}" class="text-indigo-600 font-semibold hover:text-indigo-700 transition">Sign in →</a>
            </p>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">← Back to store</a>
        </p>
    </div>
</div>
