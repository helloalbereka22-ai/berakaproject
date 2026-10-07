<div class="bg-white/95 backdrop-blur-lg p-8 rounded-3xl shadow-2xl border border-white/20">
    {{-- Logo/Brand --}}
    <div class="text-center mb-6">
        @php
            $logoPath = app(\App\Services\SettingsService::class)->get('system_logo_path', '');
        @endphp
        @if($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath))
            <img src="{{ asset('storage/' . $logoPath) }}" 
                 alt="Al-Baraka Logo"
                 class="h-16 w-16 rounded-2xl object-cover shadow-2xl shadow-orange-900/50 mb-4 mx-auto">
        @else
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-red-600 shadow-2xl shadow-orange-900/50 mb-4 relative">
                <span class="text-white font-black text-2xl">A</span>
                <div class="absolute inset-0 rounded-2xl bg-white/20 animate-pulse"></div>
            </div>
        @endif
        <h1 class="text-3xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent tracking-tight">Al-Baraka</h1>
        <p class="text-xs font-semibold text-gray-500 mt-1">Business Management System</p>
    </div>

    <form wire:submit="login" class="space-y-4">

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="admin@example.com"
                    class="appearance-none block w-full pl-11 pr-3 py-3 border-2 border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all text-sm font-medium"
                >
            </div>
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="appearance-none block w-full pl-11 pr-3 py-3 border-2 border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all text-sm font-medium"
                >
            </div>
        </div>

        {{-- Error message --}}
        @if($emailError)
            <div class="p-3 bg-red-50 border-l-4 border-red-500 rounded flex items-start gap-2">
                <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs text-red-700 font-semibold">{{ $emailError }}</p>
            </div>
        @endif

        {{-- Remember me & Forgot password --}}
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer group">
                <input wire:model="remember" id="remember" type="checkbox" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded cursor-pointer">
                <span class="text-xs font-semibold text-gray-700 group-hover:text-orange-700 transition">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 transition hover:underline">
                Forgot?
            </a>
        </div>

        {{-- Website Button & Submit Button Side by Side --}}
        <div class="grid grid-cols-2 gap-3">
            {{-- Visit Website Button --}}
            <a href="{{ route('home') }}" class="flex items-center justify-center gap-1.5 py-3 px-3 border-2 border-orange-200 bg-orange-50 hover:bg-orange-100 rounded-xl text-xs font-bold text-orange-700 hover:text-orange-800 transition-all group">
                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span>Website</span>
            </a>

            {{-- Submit Button --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="flex items-center justify-center gap-1.5 py-3 px-3 border border-transparent rounded-xl shadow-xl text-xs font-black text-white bg-gradient-to-r from-amber-500 via-orange-600 to-red-600 hover:from-amber-600 hover:via-orange-700 hover:to-red-700 focus:outline-none focus:ring-4 focus:ring-orange-500/50 transition-all disabled:opacity-60 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98]"
            >
                <span wire:loading.remove class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <span>LOGIN</span>
                </span>
                <span wire:loading class="flex items-center gap-1.5">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading…</span>
                </span>
            </button>
        </div>

    </form>
</div>
