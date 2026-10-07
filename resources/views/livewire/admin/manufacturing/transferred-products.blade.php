<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-white">Transferred Products</h2>
            <p class="text-slate-400 text-sm mt-1">View products transferred to your branch.</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    @php $stats = $this->summary; @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Transfers --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Total Transfers</p>
                    <p class="text-3xl font-extrabold text-indigo-400 font-mono">{{ number_format($stats['total_transfers']) }}</p>
                </div>
                <div class="p-2.5 bg-indigo-500/10 rounded-xl border border-indigo-500/20">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500/50 to-transparent"></div>
        </div>

        {{-- Total Quantity --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Total Quantity</p>
                    <p class="text-3xl font-extrabold text-emerald-400 font-mono">{{ number_format($stats['total_quantity'], 2) }}</p>
                </div>
                <div class="p-2.5 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
        </div>

        {{-- Total Value --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Total Value</p>
                    <p class="text-3xl font-extrabold text-amber-400 font-mono">{{ number_format($stats['total_value'], 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">ETB</p>
                </div>
                <div class="p-2.5 bg-amber-500/10 rounded-xl border border-amber-500/20">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500/50 to-transparent"></div>
        </div>

        {{-- Unique Products --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Unique Products</p>
                    <p class="text-3xl font-extrabold text-sky-400 font-mono">{{ number_format($stats['unique_products']) }}</p>
                </div>
                <div class="p-2.5 bg-sky-500/10 rounded-xl border border-sky-500/20">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-sky-500/50 to-transparent"></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Search</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Product name, SKU, reference..."
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 outline-none focus:border-indigo-500 transition">
            </div>

            {{-- Location Filter --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Location</label>
                <select wire:model.live="selectedLocationId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500 transition">
                    <option value="">All Locations</option>
                    @foreach($this->branchLocations as $location)
                        <option value="{{ $location->id }}">
                            {{ $location->name }} ({{ $location->branch->name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">From Date</label>
                <input type="date" 
                       wire:model.live="dateFrom"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500 transition">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">To Date</label>
                <input type="date" 
                       wire:model.live="dateTo"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500 transition">
            </div>
        </div>

        {{-- Clear Filters --}}
        @if($search || $selectedLocationId)
        <div class="mt-4 flex justify-end">
            <button wire:click="$set('search', ''); $set('selectedLocationId', null)"
                    class="text-xs text-slate-400 hover:text-slate-300 font-semibold transition">
                Clear Filters
            </button>
        </div>
        @endif
    </div>

    {{-- Transfers Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/80">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide">Transfer History</h3>
        </div>

        @if($this->transferredProducts->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800/50 mb-4">
                    <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="text-slate-400 text-sm font-medium">No transferred products found</p>
                <p class="text-slate-500 text-xs mt-1">Try adjusting your filters or date range.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-800/30">
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Product</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Location</th>
                            <th class="px-5 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Quantity</th>
                            <th class="px-5 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Cost</th>
                            <th class="px-5 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Total Cost</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Reference</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Created By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/50">
                        @foreach($this->transferredProducts as $transfer)
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="px-5 py-4 text-sm text-slate-300">
                                {{ $transfer->moved_at?->format('M d, Y') }}<br>
                                <span class="text-xs text-slate-500">{{ $transfer->moved_at?->format('h:i A') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-white">{{ $transfer->item->name ?? 'N/A' }}</div>
                                @if($transfer->item?->sku)
                                <div class="text-xs text-slate-500 mt-0.5">SKU: {{ $transfer->item->sku }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm text-slate-300">{{ $transfer->location->name ?? 'N/A' }}</div>
                                @if($transfer->location?->branch)
                                <div class="text-xs text-slate-500 mt-0.5">{{ $transfer->location->branch->name }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-sm font-mono font-bold text-emerald-400">{{ number_format($transfer->quantity, 2) }}</span>
                                @if($transfer->item?->unit)
                                <span class="text-xs text-slate-500 ml-1">{{ $transfer->item->unit }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-mono text-slate-300">
                                {{ number_format($transfer->unit_cost, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-mono font-semibold text-amber-400">
                                {{ number_format($transfer->total_cost, 2) }}
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-400">
                                <span class="font-mono text-xs">{{ $transfer->notes ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-400">
                                {{ $transfer->creator->name ?? 'System' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-4 border-t border-slate-800/80">
                {{ $this->transferredProducts->links() }}
            </div>
        @endif
    </div>

</div>
