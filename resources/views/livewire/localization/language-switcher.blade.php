<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-700/60 transition">
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
        </svg>
        <span>{{ strtoupper($currentLocale) }}</span>
        <svg class="w-3 h-3 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-40 bg-slate-900 border border-slate-700 rounded-xl shadow-xl py-1 z-50">
        @foreach($languages as $lang)
        <button type="button" @click="window.location.href='{{ route('language.switch') }}?lang={{ $lang['code'] }}'"
            class="flex items-center justify-between w-full px-4 py-2 text-sm text-left transition
                   {{ $currentLocale === $lang['code'] ? 'text-indigo-400 bg-indigo-900/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <span>{{ $lang['name'] }}</span>
            <span class="text-xs opacity-50 uppercase">{{ $lang['code'] }}</span>
        </button>
        @endforeach
    </div>
</div>
