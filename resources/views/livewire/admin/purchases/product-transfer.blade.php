<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl">
                <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Transfer Products</h1>
                <p class="text-slate-400 text-sm mt-0.5">Move stock between branches</p>
            </div>
        </div>
        @if(!empty($trHistory))
        <div class="px-3 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-xs text-indigo-400 font-semibold">
            {{ count($trHistory) }} transfer(s) this session
        </div>
        @endif
    </div>

    {{-- ══ Step 1: From → To ══ --}}
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">1</span>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Select Branches</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">

                {{-- Source Branch --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                            From (Source) <span class="text-red-400">*</span>
                        </span>
                    </label>
                    <div class="relative">
                        <select wire:model.live="srcBranchId"
                            class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none transition-all">
                            <option value="">— Select Source Branch —</option>
                            @foreach($this->allBranches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}
                                    @if($br->business) — {{ $br->business->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    @error('srcBranchId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Arrow --}}
                <div class="flex justify-center">
                    <div class="flex flex-col items-center gap-1">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        @if($srcBranchId && $destBranchId)
                        <span class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">Transfer</span>
                        @endif
                    </div>
                </div>

                {{-- Destination Branch --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                            To (Destination) <span class="text-emerald-400">*</span>
                        </span>
                    </label>
                    <div class="relative">
                        <select wire:model.live="destBranchId"
                            class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all disabled:opacity-40"
                            @disabled(!$srcBranchId)>
                            <option value="">— Select Destination Branch —</option>
                            @foreach($this->destBranches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}
                                    @if($br->business) — {{ $br->business->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    @error('destBranchId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

            </div>

            {{-- Live summary badge --}}
            @if($srcBranchId && $destBranchId)
            @php
                $srcB = $this->allBranches->firstWhere('id', $srcBranchId);
                $dstB = $this->destBranches->firstWhere('id', $destBranchId);
            @endphp
            <div class="mt-4 flex items-center gap-3 px-4 py-2.5 bg-slate-800/50 border border-slate-700/40 rounded-xl">
                <span class="text-xs px-2.5 py-1 bg-red-500/10 border border-red-500/20 rounded-lg text-red-300 font-semibold">
                    {{ $srcB?->name }}
                </span>
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <span class="text-xs px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-300 font-semibold">
                    {{ $dstB?->name }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ Step 2: Select Product (only after source branch selected) ══ --}}
    @if($srcBranchId)
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">2</span>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Select Product</h3>
                <span class="text-xs text-slate-500">— from source branch stock</span>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- Category --}}
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Category <span class="text-indigo-400">*</span>
                </label>
                <div class="relative">
                    <select wire:model.live="trCategoryId"
                        class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        <option value="">— Select Category —</option>
                        @forelse($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @empty
                            <option value="" disabled>No stock at this branch</option>
                        @endforelse
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                @error('trCategoryId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Item --}}
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Item <span class="text-indigo-400">*</span>
                </label>
                <div class="relative">
                    <select wire:model.live="trProductId"
                        class="w-full appearance-none bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 pr-10 text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all disabled:opacity-40"
                        @disabled(!$trCategoryId)>
                        <option value="">— Select Item —</option>
                        @foreach($this->products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                @error('trProductId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Available stock + Selling price --}}
            <div class="space-y-3" wire:key="available-qty-{{ $trProductId }}-{{ $srcBranchId }}">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Available at Source</label>
                    <div class="flex items-center justify-between px-4 py-3 rounded-xl border h-[46px]
                        {{ $trProductId && $srcBranchId ? 'bg-slate-800/50 border-emerald-500/20' : 'bg-slate-800/30 border-slate-700/50' }}">
                        @if($trProductId && $srcBranchId)
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                                <span class="text-xs text-slate-400">In stock</span>
                            </div>
                            <span class="text-base font-bold text-emerald-400 font-mono">
                                {{ number_format($this->availableQty(), 3) }}
                                <span class="text-xs text-slate-500 font-normal ml-1">units</span>
                            </span>
                        @else
                            <span class="text-sm text-slate-600">— select item —</span>
                        @endif
                    </div>
                </div>
                @if($trProductId)
                @php $sp = \App\Models\Product::withoutGlobalScopes()->find($trProductId)?->selling_price ?? 0; @endphp
                <div class="flex items-center justify-between px-4 py-2 bg-slate-800/30 border border-slate-700/40 rounded-xl">
                    <span class="text-xs text-slate-400">Selling Price</span>
                    <span class="text-sm font-mono font-bold text-indigo-300">{{ number_format($sp, 2) }} ETB</span>
                </div>
                @endif
            </div>

        </div>
    </div>
    @endif

    {{-- ══ Step 3: Qty + Notes + Transfer (only after product and dest selected) ══ --}}
    @if($trProductId && $destBranchId)
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">3</span>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Quantity & Notes</h3>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Quantity --}}
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Transfer Qty <span class="text-indigo-400">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.001" min="0.001"
                        max="{{ $this->availableQty() }}"
                        wire:model="trQuantity"
                        class="w-full bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3.5 pr-16 text-white font-mono text-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all"
                        placeholder="0.000">
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 bg-slate-700/50 rounded-r-xl border-l border-slate-600/70">
                        <span class="text-xs text-slate-400 font-semibold">units</span>
                    </div>
                </div>
                @error('trQuantity') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Notes <span class="text-indigo-400">*</span>
                    <span class="text-slate-500 font-normal normal-case ml-1">(reason)</span>
                </label>
                <textarea wire:model="trNotes" rows="2"
                    class="w-full bg-slate-800/80 border border-slate-600/70 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all resize-none"
                    placeholder="e.g. Monthly stock allocation…"></textarea>
                @error('trNotes') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
            </div>

        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="executeTransfer" wire:loading.attr="disabled"
            class="inline-flex items-center gap-2.5 px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-500/20 transition-all duration-200 disabled:opacity-60">
            <svg wire:loading wire:target="executeTransfer" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg wire:loading.remove wire:target="executeTransfer" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <span wire:loading.remove wire:target="executeTransfer">Transfer Now</span>
            <span wire:loading wire:target="executeTransfer">Transferring…</span>
        </button>
    </div>
    @endif

    {{-- ══ History Table ══ --}}
    @if(!empty($trHistory))
    <div class="bg-slate-900/80 border border-indigo-500/20 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Transfer History</h3>
                <span class="text-xs text-slate-500">this session</span>
            </div>
            <span class="px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-sm font-bold text-indigo-400">
                {{ count($trHistory) }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-800/40 border-b border-slate-700/50">
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="text-right px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">From</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">To</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Notes</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    @foreach($trHistory as $i => $tr)
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="px-5 py-3.5 text-xs text-slate-500 font-mono">{{ $i + 1 }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 bg-slate-800/60 border border-slate-700/50 rounded-lg text-xs text-slate-300">
                                {{ $tr['category'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-sm font-semibold text-white">{{ $tr['product'] }}</td>
                        <td class="px-5 py-3.5 text-right text-sm font-mono font-bold text-indigo-400">
                            {{ number_format($tr['qty'], 3) }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-300 font-medium">
                                {{ $tr['from'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-xs text-emerald-300 font-medium">
                                {{ $tr['to'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 max-w-xs text-sm text-slate-300">{{ $tr['notes'] }}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-400 font-mono whitespace-nowrap">{{ $tr['at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══ ALL Transfer History from DB ══ --}}
    <div class="bg-slate-900/80 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-slate-700/50"
             style="background: linear-gradient(90deg, rgba(99,102,241,0.07) 0%, transparent 60%);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-purple-500 rounded-full"></div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">📦 All Transferred Items</h3>
                    <span class="px-2.5 py-1 bg-purple-500/10 border border-purple-500/20 rounded-xl text-xs font-bold text-purple-400">
                        {{ $this->transferHistory->count() }} records
                    </span>
                </div>
            </div>

            {{-- Search Filters --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter by Branch</label>
                    <select wire:model.live="searchBranch"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:border-purple-500 outline-none transition">
                        <option value="">All Branches</option>
                        @foreach($this->filterBranches as $id => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter by Category</label>
                    <select wire:model.live="searchCategory"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:border-purple-500 outline-none transition">
                        <option value="">All Categories</option>
                        @foreach($this->filterCategories as $id => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter by Item</label>
                    <select wire:model.live="searchItem"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:border-purple-500 outline-none transition">
                        <option value="">All Items</option>
                        @foreach($this->filterItems as $id => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if($searchBranch || $searchCategory || $searchItem)
            <div class="mt-2">
                <button wire:click="$set('searchBranch',''); $set('searchCategory',''); $set('searchItem','')"
                    class="text-xs text-purple-400 hover:text-purple-300 underline">✕ Clear filters</button>
            </div>
            @endif
        </div>

        @if($this->transferHistory->isEmpty())
        <div class="py-16 text-center">
            <div class="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm">No transfer records found</p>
            @if($searchBranch || $searchCategory || $searchItem)
            <button wire:click="$set('searchBranch',''); $set('searchCategory',''); $set('searchItem','')"
                class="mt-3 text-xs text-purple-400 hover:text-purple-300 underline">Clear filters</button>
            @endif
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-800/40 border-b border-slate-700/50">
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Item</th>
                        <th class="text-right px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">From Branch</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">To Branch</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Notes</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    @foreach($this->transferHistory as $i => $tr)
                    <tr class="hover:bg-slate-800/20 transition-colors">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $i + 1 }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 bg-slate-800 border border-slate-700 rounded-lg text-xs text-slate-300">
                                {{ $tr->category_name }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-sm font-semibold text-white">{{ $tr->product_name }}</td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-indigo-400 text-sm">
                            {{ number_format($tr->quantity, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-300">
                                {{ $tr->from_branch }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div>
                                <span class="px-2 py-0.5 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-xs text-emerald-300">
                                    {{ $tr->to_branch }}
                                </span>
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $tr->to_business }}</div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400 max-w-[150px] truncate">{{ $tr->user_notes ?: '—' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-400 font-mono whitespace-nowrap">
                            {{ $tr->moved_at ? \Carbon\Carbon::parse($tr->moved_at)->format('d M Y H:i') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (event) => {
                if (typeof toastr !== 'undefined') {
                    const e = event[0] ?? event;
                    if (e.type === 'success') toastr.success(e.message);
                    else if (e.type === 'error') toastr.error(e.message);
                }
            });
        });
    </script>

</div>
