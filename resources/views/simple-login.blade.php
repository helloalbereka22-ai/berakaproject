<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login - Al-Baraka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <div class="bg-white p-8 md:p-10 rounded-3xl shadow-2xl border border-gray-100">
            
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 shadow-lg shadow-indigo-500/30 mb-4">
                    <span class="text-white font-black text-2xl">A</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Al-Baraka</h1>
                <p class="text-gray-500 mt-2 font-medium">Sign in to your account</p>
            </div>

            {{-- Login Form (Standard HTML Form) --}}
            <form method="POST" action="/simple-login" class="space-y-6">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder="admin@example.com"
                        value="{{ old('email') }}"
                        class="appearance-none block w-full px-4 py-3.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base"
                    >
                    @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="appearance-none block w-full px-4 py-3.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-base"
                    >
                    @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- General Error --}}
                @if(session('error'))
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                </div>
                @endif

                {{-- Remember Me & Forgot Password --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer">
                        <span class="text-sm font-medium text-gray-700">Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition">
                        Forgot password?
                    </a>
                </div>

                {{-- Submit Button --}}
                <div>
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign In
                    </button>
                </div>

                {{-- Register Link --}}
                <div class="text-center pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 transition">
                            Create one
                        </a>
                    </p>
                </div>

            </form>

            {{-- Back to Home --}}
            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-indigo-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Home
                </a>
            </div>

        </div>

        {{-- Debug Info --}}
        <div class="mt-6 text-center text-sm text-gray-500">
            <p><strong>Note:</strong> This is a simple HTML form (non-Livewire) for testing.</p>
            <p class="mt-2">Route: <code class="bg-white px-2 py-1 rounded">{{ url()->current() }}</code></p>
        </div>
    </div>

</body>
</html>
