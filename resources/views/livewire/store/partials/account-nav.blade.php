@php
    $currentRoute = request()->route()?->getName() ?? '';
@endphp
<nav class="flex items-center gap-1 mb-8 border-b border-gray-200 pb-0 -mx-1 overflow-x-auto">
    @foreach([
        ['route' => 'store.account.profile', 'label' => 'Profile',  'icon' => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'store.account.orders', 'label' => 'My Orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ] as $nav)
    <a href="{{ route($nav['route']) }}"
       class="flex items-center gap-1.5 px-4 py-3 text-sm font-semibold border-b-2 transition whitespace-nowrap
              {{ $currentRoute === $nav['route']
                  ? 'border-indigo-600 text-indigo-700'
                  : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $nav['icon'] }}"/>
        </svg>
        {{ $nav['label'] }}
    </a>
    @endforeach
</nav>
