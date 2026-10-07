<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Inventory Overview</h2>
            <p class="text-sm text-slate-400 mt-1">Global view of stock levels across all locations.</p>
        </div>
        <div class="flex gap-2">
            @can('inventory.update')
            <button wire:click="$dispatch('open-adjustment-modal')" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition border border-slate-700">
                Adjust Stock
            </button>
            <button wire:click="$dispatch('open-transfer-modal')" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
                New Transfer
            </button>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 p-4 bg-slate-900 border border-slate-800 rounded-2xl">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search product..."
               class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
               
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
        </select>
        
        <select wire:model.live="locationId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Locations</option>
            @foreach($locations as $l)<option value="{{ $l['id'] }}">{{ $l['name'] }}</option>@endforeach
        </select>

        <select wire:model.live="productType" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Product Types</option>
            @foreach($productTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Items</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($kpis['total_items']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Unique stock records</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Value</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($kpis['total_value'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-1">Estimated Cost Value (ETB)</p>
        </div>
        <div class="bg-slate-900 border border-amber-500/30 rounded-2xl p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 rounded-full bg-amber-500/10 blur-xl"></div>
            <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider">Low Stock Alerts</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($kpis['low_stock']) }}</p>
            <p class="text-xs text-slate-400 mt-1">Below minimum threshold</p>
        </div>
        <div class="bg-slate-900 border border-red-500/30 rounded-2xl p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 rounded-full bg-red-500/10 blur-xl"></div>
            <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Out of Stock</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($kpis['out_of_stock']) }}</p>
            <p class="text-xs text-slate-400 mt-1">Needs immediate attention</p>
        </div>
    </div>

    {{-- Stock Types Breakdown (Small pills) --}}
    <div class="flex flex-wrap gap-3">
        @foreach($byType as $type => $count)
            @php
                $tc = ['sale_product' => 'emerald', 'raw_material' => 'sky', 'packing_material' => 'violet', 'finished_product' => 'amber'][$type] ?? 'slate';
            @endphp
            <div class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-{{ $tc }}-400"></span>
                <span class="text-sm text-slate-300 font-medium">{{ $productTypes[$type] ?? 'Unknown' }}</span>
                <span class="text-xs text-slate-500 ml-2">{{ $count }}</span>
            </div>
        @endforeach
    </div>

    {{-- Data Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase">Item</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase">Type</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase">Location</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase">Qty</th>
                        <th class="text-center px-5 py-4 text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($stockLevels as $stock)
                        @php $item = $stock->item; @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white">{{ $item->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $item->sku ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $typeColors = ['sale_product' => 'emerald', 'raw_material' => 'sky', 'packing_material' => 'violet', 'finished_product' => 'amber'];
                                    $tc = $typeColors[$item->type ?? ''] ?? 'slate';
                                @endphp
                                <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-{{ $tc }}-500/10 text-{{ $tc }}-400 border border-{{ $tc }}-500/20">
                                    {{ $productTypes[$item->type ?? ''] ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-slate-300 font-medium">{{ optional($stock->location)->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ optional(optional($stock->location)->branch)->name ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-200">
                                {{ number_format($stock->quantity, 2) }} <span class="text-xs text-slate-500">{{ $item->unit ?? '' }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($stock->isOutOfStock())
                                    <span class="inline-flex px-2.5 py-1 rounded bg-red-500/10 text-red-400 border border-red-500/20 text-xs font-semibold">Out of Stock</span>
                                @elseif($stock->isLowStock())
                                    <span class="inline-flex px-2.5 py-1 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-semibold">Low Stock</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-semibold">In Stock</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @can('inventory.update')
                                <button wire:click="$dispatch('open-adjustment-modal', { id: {{ $stock->id }} })" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
                                    Adjust
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p class="text-slate-500">No stock records found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-800">
            {{ $stockLevels->links() }}
        </div>
    </div>
</div>
