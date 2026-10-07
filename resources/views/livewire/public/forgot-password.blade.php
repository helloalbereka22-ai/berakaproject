<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-2xl border border-orange-100">
        {{-- Header --}}
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-red-600 shadow-2xl shadow-orange-900/50 mb-4">
                <span class="text-white font-black text-2xl">A</span>
            </div>
            <h2 class="text-3xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent tracking-tight">
                Reset Password
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.
            </p>
        </div>

        {{-- Success Message with Reset Link --}}
        @if ($statusMessage)
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg space-y-3">
                <p class="text-sm text-emerald-700 font-semibold">{{ $statusMessage }}</p>
                @if($resetToken)
                <div class="bg-white rounded p-3 border border-emerald-200">
                    <p class="text-xs text-gray-600 font-semibold mb-2">Reset Link:</p>
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            readonly
                            value="{{ route('password.reset', ['token' => $resetToken, 'email' => $resetEmail]) }}"
                            class="flex-1 text-xs bg-gray-100 border border-gray-300 rounded px-2 py-1 font-mono"
                        />
                        <button 
                            type="button"
                            onclick="const link = document.querySelector('input[readonly]'); link.select(); document.execCommand('copy'); alert('Link copied to clipboard!');"
                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded transition"
                        >
                            Copy
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">👆 Click the link or copy it to reset your password</p>
                </div>
                @endif
            </div>
        @endif

        {{-- Form --}}
        <form wire:submit="sendResetLink" class="mt-8 space-y-6">
            {{-- Email Input --}}
            <div>
                <label for="email" class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-wide">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input 
                        wire:model="email" 
                        id="email" 
                        type="email" 
                        autocomplete="email" 
                        required
                        placeholder="you@example.com"
                        class="appearance-none block w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all text-sm font-medium"
                    >
                </div>
                @error('email')
                    <p class="text-red-500 text-xs font-semibold mt-2 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.414-1.414L10 16.586 7.313 13.899a1 1 0 00-1.414 1.414l3.5 3.5a1 1 0 001.414 0l8.688-8.688z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('login') }}" class="flex items-center justify-center py-3 px-3 border-2 border-orange-200 bg-orange-50 hover:bg-orange-100 rounded-xl text-xs font-bold text-orange-700 hover:text-orange-800 transition-all">
                    ← Back
                </a>
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex items-center justify-center gap-2 py-3 px-3 border border-transparent rounded-xl shadow-xl text-xs font-bold text-white bg-gradient-to-r from-amber-500 via-orange-600 to-red-600 hover:from-amber-600 hover:via-orange-700 hover:to-red-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove>Send Link</span>
                    <span wire:loading class="flex items-center gap-1">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sending…
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
