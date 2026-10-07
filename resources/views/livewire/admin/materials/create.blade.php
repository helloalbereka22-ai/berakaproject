<div class="max-w-3xl mx-auto space-y-6">

    {{-- Page header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.materials.index') }}" wire:navigate
            class="p-2 rounded-xl text-slate-500 hover:text-white hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-white">Add Material</h2>
            <p class="text-sm text-slate-400 mt-0.5">Register a new raw or packaging material.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">

        {{-- ── CLASSIFICATION ───────────────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Classification</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                {{-- Business --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Business <span class="text-red-400">*</span></label>
                    <select wire:model.live="businessId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select business...</option>
                        @foreach($businesses as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </select>
                    @error('businessId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Material Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Material Type <span class="text-red-400">*</span></label>
                    <select wire:model.live="type"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        @foreach($materialTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Category <span class="text-red-400">*</span></label>
                    <select wire:model.live="categoryId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500
                            {{ empty($categories) ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ empty($categories) ? 'disabled' : '' }}>
                        <option value="">{{ empty($categories) ? 'Select business & type first...' : 'Select category...' }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}">{{ $c['icon'] ?? '' }} {{ $c['name'] }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Type hint badge --}}
            <div class="mt-3 flex items-center gap-2">
                @if($typeConfig['isRaw'])
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-400">
                        🌾 Raw Material — bulk agricultural or liquid input
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 border border-blue-500/30 text-blue-400">
                        📦 Packaging Material — physical container or wrapping
                    </span>
                @endif
            </div>

            {{-- Category info banner (appears after selecting category) --}}
            @if(!empty($selectedCategory))
                <div class="mt-3 flex items-center gap-3 p-3 rounded-xl bg-slate-800/60 border border-slate-700">
                    @if($selectedCategory['icon'])
                        <span class="text-2xl leading-none">{{ $selectedCategory['icon'] }}</span>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-200">{{ $selectedCategory['name'] }}</p>
                        @if($selectedCategory['default_unit'])
                            <p class="text-xs text-slate-400 mt-0.5">
                                Default unit: <span class="text-indigo-400 font-medium">{{ $selectedCategory['default_unit'] }}</span>
                                — pre-filled below
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── PRODUCT IDENTITY ─────────────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Product Identity</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Name (full width) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Material Name <span class="text-red-400">*</span>
                    </label>
                    <input wire:model="name" type="text"
                        placeholder="{{ $typeConfig['isRaw'] ? 'e.g. Sesame Seeds Grade A' : 'e.g. PET Bottle 500ml' }}"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- SKU (shown for both) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">SKU</label>
                    <input wire:model="sku" type="text"
                        placeholder="{{ $typeConfig['isRaw'] ? 'e.g. RM-SES-001' : 'e.g. PKG-BTL-500' }}"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                    @error('sku') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Unit of Measure — dropdown pre-filled from category default_unit --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Unit of Measure <span class="text-red-400">*</span>
                    </label>
                    <select wire:model="unit"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select unit...</option>
                        @foreach($unitOptions as $key => $label)
                            <option value="{{ $key }}" @selected($unit === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('unit') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Barcode — Packaging only --}}
                @if($typeConfig['isPacking'])
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Barcode / ISBN</label>
                    <input wire:model="barcode" type="text" placeholder="e.g. 6281234567890"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                    @error('barcode') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Package Size — Packaging only --}}
                @if($typeConfig['isPacking'])
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Package Size</label>
                    <input wire:model="packageSize" type="text" placeholder="e.g. 500ml, 1L, 50pcs/box"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                    @error('packageSize') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Image — Packaging only --}}
                @if($typeConfig['isPacking'])
                <div class="{{ $typeConfig['isPacking'] ? 'sm:col-span-2' : '' }}">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Image (Optional)</label>
                    <input wire:model="image" type="file" accept="image/*"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-300
                               file:mr-3 file:bg-indigo-600 file:text-white file:text-xs file:font-semibold
                               file:border-0 file:rounded-lg file:px-3 file:py-1.5 focus:border-indigo-500 outline-none">
                    @error('image') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Description (full width, both types) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Description</label>
                    <textarea wire:model="description" rows="2"
                        placeholder="{{ $typeConfig['isRaw'] ? 'Quality grade, origin, supplier notes...' : 'Dimensions, material, supplier notes...' }}"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none resize-none"></textarea>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── PRICING ──────────────────────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Pricing</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Cost Price (ETB) <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500">ETB</span>
                        <input wire:model.live="costPrice" type="number" step="0.0001" min="0"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-12 pr-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    </div>
                    @error('costPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── STOCK THRESHOLDS ─────────────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Stock Thresholds</p>
            <p class="text-xs text-slate-500 mb-4">
                @if($typeConfig['isRaw'])
                    Set reorder alerts for when seed/input stock runs low.
                @else
                    Set min/max levels to trigger restocking of packaging.
                @endif
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Reorder Point</label>
                    <input wire:model="reorderPoint" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @if($unit) <p class="text-xs text-slate-600 mt-1">in {{ $unit }}</p> @endif
                    @error('reorderPoint') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Min Stock</label>
                    <input wire:model="minStock" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @if($unit) <p class="text-xs text-slate-600 mt-1">in {{ $unit }}</p> @endif
                    @error('minStock') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Max Stock</label>
                    <input wire:model="maxStock" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @if($unit) <p class="text-xs text-slate-600 mt-1">in {{ $unit }}</p> @endif
                    @error('maxStock') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── INITIAL STOCK & VALUATION ────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Initial Stock &amp; Valuation</p>
            <p class="text-xs text-slate-500 mb-4">Optionally record the opening stock quantity when registering this material.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Initial Quantity @if($unit) <span class="text-slate-600 font-normal">({{ $unit }})</span> @endif
                    </label>
                    <input wire:model.live="initialQuantity" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('initialQuantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if((float)$initialQuantity > 0)
                <div class="sm:col-span-2 p-4 rounded-xl bg-slate-800 border border-slate-700 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-slate-300">Total Opening Value</span>
                        <span class="text-lg font-bold text-emerald-400">
                            {{ number_format($this->calculated_total_value, 2) }}
                            <span class="text-sm text-slate-500">ETB</span>
                        </span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                            Store In Location <span class="text-red-400">*</span>
                        </label>
                        <select wire:model="locationId"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                            <option value="">Select factory or warehouse...</option>
                            @foreach($locations as $l)
                                <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                            @endforeach
                        </select>
                        @error('locationId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── STATUS ───────────────────────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Visibility &amp; Status</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Status</label>
                    <div class="flex gap-2">
                        @foreach($statuses as $key => $label)
                            <label class="flex-1 cursor-pointer">
                                <input wire:model="status" type="radio" value="{{ $key }}" class="sr-only peer">
                                <div class="text-center px-3 py-2 rounded-xl border text-xs font-semibold transition
                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 peer-checked:text-indigo-400
                                    border-slate-700 text-slate-400 hover:border-slate-600">
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('status') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ── ACTIONS ──────────────────────────────────────────────── --}}
        <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
            <a href="{{ route('admin.materials.index') }}" wire:navigate
                class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                Cancel
            </a>
            <button wire:click="save" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition
                       disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <span wire:loading wire:target="save">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                </span>
                <span wire:loading.remove wire:target="save">Register Material</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </div>
</div>
