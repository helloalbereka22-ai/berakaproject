<div class="relative" x-data="{ open: false }" @click.outside="open = false">

    {{-- Trigger --}}
    <button @click="open = !open"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold
               text-gray-600 hover:text-indigo-700 hover:bg-indigo-50
               border border-gray-200 hover:border-indigo-300 transition select-none">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
        </svg>
        <span class="uppercase font-bold tracking-wide">{{ app()->getLocale() }}</span>
        <svg class="w-3 h-3 text-gray-400 transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute end-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-xl py-1 z-50">

        @php
            $langs = [
                ['code' => 'en', 'name' => 'English',     'flag' => '🇬🇧'],
                ['code' => 'am', 'name' => 'አማርኛ',        'flag' => '🇪🇹'],
                ['code' => 'ar', 'name' => 'العربية',      'flag' => '🇸🇦'],
                ['code' => 'om', 'name' => 'Afaan Oromoo', 'flag' => '🇪🇹'],
            ];
            $current = app()->getLocale();
        @endphp

        @foreach($langs as $lang)
        <a href="{{ url('/set-language') }}?lang={{ $lang['code'] }}"
           onclick="event.preventDefault(); window.location.href='{{ url('/set-language') }}?lang={{ $lang['code'] }}';"
            class="flex items-center justify-between w-full px-4 py-2.5 text-sm transition
                   {{ $current === $lang['code']
                       ? 'text-indigo-700 bg-indigo-50 font-bold'
                       : 'text-gray-700 hover:bg-gray-50' }}">
            <div class="flex items-center gap-2">
                <span class="text-base">{{ $lang['flag'] }}</span>
                <span>{{ $lang['name'] }}</span>
            </div>
            @if($current === $lang['code'])
            <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            @endif
        </a>
        @endforeach

    </div>
</div>
