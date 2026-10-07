<x-layouts.app title="Dashboard">
    <div x-data="{ currentTab: 'dashboard' }"
         @show-role-assignment.window="currentTab = 'role-assignment'"
         @show-category-manager.window="currentTab = 'category-manager'"
         @show-product-manager.window="currentTab = 'product-manager'">

        <!-- Dashboard Tab -->
        <div x-show="currentTab === 'dashboard'">
            @if(Auth::user()->roles()->whereIn('name', ['Super Admin', 'Admin'])->exists())
                @livewire('admin.dashboard.main-dashboard')
            @else                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h1 class="text-3xl font-bold text-gray-900">Welcome to Albareka, {{ Auth::user()->name }}</h1>
                    <p class="mt-2 text-gray-500 text-lg">Business Manager Dashboard</p>
                </div>
            @endif
        </div>

        <!-- Role Assignment Tab -->
        <div x-show="currentTab === 'role-assignment'" style="display: none;">
            @livewire('identity.role-assignment-manager')
        </div>

        <!-- Category Manager Tab -->
        <div x-show="currentTab === 'category-manager'" style="display: none;">
            @livewire('catalog.category-manager')
        </div>

        <!-- Product Manager Tab -->
        <div x-show="currentTab === 'product-manager'" style="display: none;">
            @livewire('catalog.product-manager')
        </div>

    </div>
</x-layouts.app>
