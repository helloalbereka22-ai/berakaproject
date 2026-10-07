<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-100">Translation Manager</h1>
            <p class="text-slate-400 text-sm mt-0.5">Manage system strings and database content translations</p>
        </div>
        {{-- Locale selector --}}
        <div class="flex items-center gap-3">
            <label class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Locale</label>
            <select wire:model.live="locale"
                class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                @foreach($languages as $code => $name)
                    <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="px-4 py-3 bg-emerald-900/40 border border-emerald-700/50 text-emerald-300 rounded-xl text-sm">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="px-4 py-3 bg-red-900/40 border border-red-700/50 text-red-300 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-slate-800">
        @foreach(['system' => 'System Strings (lang/ files)', 'content' => 'Content Translations (Database)'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-4 py-2.5 text-sm font-semibold transition border-b-2
                   {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="searchTerm" type="text" placeholder="Search keys or values..."
            class="w-full pl-9 pr-4 py-2 bg-slate-800 border border-slate-700 text-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
    </div>

    {{-- ═══ SYSTEM STRINGS TAB ═══════════════════════════════════════════ --}}
    @if($activeTab === 'system')

    <div class="bg-slate-900/60 border border-slate-800/60 rounded-2xl overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800/60 flex items-center justify-between">
            <p class="text-xs text-slate-400">
                Editing <span class="text-indigo-400 font-bold">lang/{{ $locale }}/app.php</span>
                &nbsp;·&nbsp; {{ count($systemStrings) }} strings
            </p>
        </div>

        @if(empty($systemStrings))
        <div class="text-center py-12 text-slate-500 text-sm">
            No strings found. Create <code class="text-indigo-400">lang/{{ $locale }}/app.php</code> first.
        </div>
        @else
        <div class="divide-y divide-slate-800/40">
            @foreach($systemStrings as $dotKey => $value)
            @php
                $parts = explode('.', $dotKey, 3);
                $group = $parts[0] ?? '';
                $key   = implode('.', array_slice($parts, 1));
            @endphp
            <div class="flex items-start gap-4 px-5 py-3 hover:bg-slate-800/30 transition group">
                <div class="flex-1 min-w-0">
                    <p class="font-mono text-xs text-slate-400">{{ $dotKey }}</p>
                    <p class="text-sm text-slate-200 mt-0.5 leading-snug">{{ $value }}</p>
                </div>
                <button wire:click="openSysEdit('{{ $group }}', '{{ $key }}', '{{ addslashes($value) }}')"
                    class="shrink-0 opacity-0 group-hover:opacity-100 px-2.5 py-1 text-xs font-semibold text-indigo-400 border border-indigo-700/50 rounded-lg hover:bg-indigo-900/30 transition">
                    Edit
                </button>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- System string edit modal --}}
    @if($showSysForm)
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-lg shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                <div>
                    <h3 class="font-bold text-slate-100 text-sm">Edit Translation</h3>
                    <p class="font-mono text-xs text-indigo-400 mt-0.5">{{ $editGroup }}.{{ $editKey }}</p>
                </div>
                <button wire:click="$set('showSysForm', false)" class="text-slate-500 hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">
                        Translation ({{ strtoupper($locale) }})
                    </label>
                    <textarea wire:model="editValue" rows="3"
                        dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
                        class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 resize-none"></textarea>
                    @error('editValue') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showSysForm', false)"
                        class="px-4 py-2 text-sm text-slate-400 hover:text-slate-200 border border-slate-700 rounded-xl transition">Cancel</button>
                    <button wire:click="saveSysTranslation"
                        wire:loading.attr="disabled"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition">
                        <span wire:loading.remove wire:target="saveSysTranslation">Save</span>
                        <span wire:loading wire:target="saveSysTranslation">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif {{-- end system tab --}}

    {{-- ═══ CONTENT TRANSLATIONS TAB ══════════════════════════════════════ --}}
    @if($activeTab === 'content')

    {{-- Type selector --}}
    <div class="flex flex-wrap items-center gap-3">
        <label class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Content Type</label>
        <select wire:model.live="contentType"
            class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
            <option value="">— Select type —</option>
            @foreach($contentTypes as $class => $label)
                <option value="{{ $class }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if($contentType)
    <div class="bg-slate-900/60 border border-slate-800/60 rounded-2xl overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800/60">
            <p class="text-xs text-slate-400">
                {{ $contentTypes[$contentType] ?? $contentType }} ·
                <span class="text-indigo-400 font-bold">{{ strtoupper($locale) }}</span>
            </p>
        </div>

        @if($contentTranslations->isEmpty())
        <div class="text-center py-12 text-slate-500 text-sm">
            No translations yet for this type and locale.
            <br><span class="text-xs text-slate-600">Use product/article edit forms to add translations.</span>
        </div>
        @else
        <div class="divide-y divide-slate-800/40">
            @foreach($contentTranslations as $t)
            <div class="flex items-start gap-4 px-5 py-3 hover:bg-slate-800/30 transition group">
                <div class="shrink-0 text-right w-20">
                    <p class="text-xs text-slate-500">ID {{ $t->translatable_id }}</p>
                    <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $t->field }}</p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-200 leading-snug line-clamp-2"
                       dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">{{ $t->value }}</p>
                </div>
                <div class="flex gap-2 shrink-0 opacity-0 group-hover:opacity-100 transition">
                    <button wire:click="openContentEdit({{ $t->translatable_id }}, '{{ $t->field }}', '{{ addslashes($t->value) }}')"
                        class="px-2.5 py-1 text-xs font-semibold text-indigo-400 border border-indigo-700/50 rounded-lg hover:bg-indigo-900/30 transition">Edit</button>
                    <button wire:click="deleteContentTranslation({{ $t->id }})"
                        wire:confirm="Delete this translation?"
                        class="px-2.5 py-1 text-xs font-semibold text-red-400 border border-red-700/50 rounded-lg hover:bg-red-900/30 transition">Del</button>
                </div>
            </div>
            @endforeach
        </div>
        <div class="px-5 py-3 border-t border-slate-800/60">
            {{ $contentTranslations->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- Content edit modal --}}
    @if($showContentForm)
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-lg shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                <div>
                    <h3 class="font-bold text-slate-100 text-sm">Edit Content Translation</h3>
                    <p class="text-xs text-slate-400 mt-0.5">ID {{ $contentId }} · {{ $contentField }} · {{ strtoupper($locale) }}</p>
                </div>
                <button wire:click="$set('showContentForm', false)" class="text-slate-500 hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <textarea wire:model="contentValue" rows="4"
                    dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 resize-none"></textarea>
                @error('contentValue') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showContentForm', false)"
                        class="px-4 py-2 text-sm text-slate-400 hover:text-slate-200 border border-slate-700 rounded-xl transition">Cancel</button>
                    <button wire:click="saveContentTranslation"
                        wire:loading.attr="disabled"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition">Save</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif {{-- end content tab --}}

</div>
