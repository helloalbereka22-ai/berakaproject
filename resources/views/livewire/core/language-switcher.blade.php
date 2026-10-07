<div class="relative inline-block text-left">
    <div class="flex items-center gap-2">
        <button wire:click="switchLocale('en')" class="px-2 py-1 text-xs rounded font-bold {{ $currentLocale === 'en' ? 'bg-indigo-800 text-white' : 'bg-indigo-500 text-indigo-100 hover:bg-indigo-400' }}">
            EN
        </button>
        <button wire:click="switchLocale('ar')" class="px-2 py-1 text-xs rounded font-bold {{ $currentLocale === 'ar' ? 'bg-indigo-800 text-white' : 'bg-indigo-500 text-indigo-100 hover:bg-indigo-400' }}">
            العربية (AR)
        </button>
    </div>
</div>
