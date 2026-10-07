<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-300 hover:text-white transition-colors bg-slate-800/50 hover:bg-slate-800 rounded-lg border border-slate-700/50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        <span>{{ $languages[$currentLocale] }}</span>
        <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-40 rounded-xl shadow-lg bg-slate-800 border border-slate-700 overflow-hidden z-50 text-left rtl:right-auto rtl:left-0">
        <div class="py-1">
            @foreach($languages as $code => $label)
                <button wire:click="switchLanguage('{{ $code }}')" class="w-full text-left rtl:text-right px-4 py-2.5 text-sm {{ $currentLocale === $code ? 'bg-violet-500/10 text-violet-400 font-semibold' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }} transition-colors flex items-center justify-between">
                    {{ $label }}
                    @if($currentLocale === $code)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>
