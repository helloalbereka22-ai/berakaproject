<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">

        {{-- Logo / header --}}
        <div class="text-center mb-8">
            <a href="{{ route('store.index') }}" class="inline-flex items-center gap-2 justify-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center">
                    <span class="text-white font-extrabold">A</span>
                </div>
                <span class="text-2xl font-extrabold text-gray-900">Al-Baraka <span class="text-indigo-600">Store</span></span>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-gray-500 text-sm mt-1">Sign in to your customer account</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
            <form wire:submit="login" class="space-y-5">

                {{-- General error --}}
                @if($errors->any())
                    <div class="flex items-start gap-2 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input wire:model="email" id="email" type="email" autocomplete="email"
                        class="w-full px-4 py-2.5 border rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition
                               @error('email') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input wire:model="password" id="password" type="password" autocomplete="current-password"
                        class="w-full px-4 py-2.5 border rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition
                               @error('password') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                    @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded accent-indigo-600">
                        Remember me
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-70 cursor-not-allowed"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                    <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove>Sign In</span>
                    <span wire:loading>Signing in...</span>
                </button>
            </form>

            {{-- Register link --}}
            <p class="text-center text-sm text-gray-500 mt-6">
                Don't have an account?
                <a href="{{ route('customer.register') }}" class="text-indigo-600 font-semibold hover:text-indigo-700 transition">Create one →</a>
            </p>
        </div>

        {{-- Back to store --}}
        <p class="text-center text-xs text-gray-400 mt-6">
            <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">← Back to store</a>
        </p>
    </div>
</div>
