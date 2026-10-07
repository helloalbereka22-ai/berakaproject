<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ Session::get('is_rtl') ? 'rtl' : 'ltr' }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <x-seo 
        title="{{ $title ?? 'Albareka' }}" 
        description="{{ $description ?? 'Enterprise Business Management Platform' }}" 
    />
    
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
            }
        </script>
    @endif
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col font-sans {{ Session::get('is_rtl') ? 'rtl' : 'ltr' }}">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600 tracking-tight">Albareka</a>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    @livewire('localization.language-switcher')
                    
                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <div class="flex-grow flex flex-col md:flex-row w-full max-w-7xl mx-auto">
        <!-- Sidebar Navigation (Responsive) -->
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 flex-shrink-0 md:min-h-[calc(100vh-4rem)]">
            <nav class="p-4 space-y-1">
                @auth
                <a href="/" class="block px-3 py-2 rounded-xl text-sm font-medium text-indigo-700 bg-indigo-50">Dashboard</a>
                
                @if(Auth::user()->roles()->where('name', 'Super Admin')->exists())
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</p>
                    </div>
                    <a href="#" onclick="window.dispatchEvent(new CustomEvent('show-role-assignment'))" class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">Role Assignment</a>
                    <a href="#" onclick="window.dispatchEvent(new CustomEvent('show-category-manager'))" class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">Categories</a>
                @endif
                
                <div class="pt-4 pb-2">
                    <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Catalog</p>
                </div>
                <a href="#" onclick="window.dispatchEvent(new CustomEvent('show-product-manager'))" class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">Products</a>

                <div class="pt-4 pb-2">
                    <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">CRM</p>
                </div>
                <a href="{{ route('business.customers') }}" class="block px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">Customers</a>
                @endauth
            </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 md:ml-64 flex flex-col min-h-screen w-full transition-all duration-300">
            <div class="flex-1 p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 p-6 text-center text-sm text-gray-500 mt-auto">
                &copy; {{ date('Y') }} Albareka Enterprise. All rights reserved.
            </footer>
        </main>
    </div>

    @livewireScripts
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
    <script>
        // Simple mobile menu toggle logic for the application shell
        document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('flex');
            } else {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
            }
        });
    </script>
</body>
</html>
