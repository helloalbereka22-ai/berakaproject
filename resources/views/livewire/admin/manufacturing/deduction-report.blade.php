<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-orange-500 flex items-center justify-center shadow-lg shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-white">Deduction Report</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">History of raw material deductions for manufacturing.</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-4 shadow-xl">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Date From --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">From</label>
                <input wire:model.lazy="dateFrom" type="date"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-amber-500">
            </div>
            {{-- Date To --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">To</label>
                <input wire:model.lazy="dateTo" type="date"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-amber-500">
            </div>
            {{-- Business --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Business</label>
                <select wire:model.live="businessId"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-amber-500">
                    <option value="">All</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Branch --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Branch</label>
                <select wire:model.live="branchId"
                    @if($branches->isEmpty()) disabled @endif
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-amber-500 disabled:opacity-40">
                    <option value="">{{ $businessId ? 'All Branches' : '—' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Reset --}}
            <div class="lg:col-span-2 flex items-end gap-2">
                <button wire:click="resetFilters"
                    class="flex-1 bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold py-2 rounded-lg transition">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    {{-- Report Table --}}
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">

        {{-- Summary --}}
        @if($rows->count() > 0)
        <div class="px-5 py-3 border-b border-slate-800 bg-slate-950/30 flex justify-between items-center">
            <span class="text-xs text-slate-500">{{ $rows->total() }} deduction(s)</span>
            <div class="flex gap-6 text-xs">
                <div>
                    <span class="text-slate-500">Total Deducted:</span>
                    <span class="font-mono font-bold text-amber-400 ml-2">{{ number_format($totalQuantity, 3) }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Total Value:</span>
                    <span class="font-mono font-bold text-emerald-400 ml-2">{{ number_format($totalCost, 2) }}</span>
                </div>
            </div>
        </div>
        @endif

        @if($rows->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                            <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                            <th class="text-right px-5 py-3.5 font-bold">Deducted Qty</th>
                            <th class="text-right px-5 py-3.5 font-bold">Unit Cost</th>
                            <th class="text-right px-5 py-3.5 font-bold">Total Cost</th>
                            <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                            <th class="text-left px-5 py-3.5 font-bold">Deducted By</th>
                            <th class="text-left px-5 py-3.5 font-bold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/50">
                        @foreach($rows as $row)
                            @php
                                $itemName = $row->product?->name ?? 'Unknown';
                                $branchName = $row->location?->branch?->name ?? 'Unknown';
                                $userName = $row->creator?->name ?? 'System';
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-white">{{ $itemName }}</p>
                                    <p class="text-[10px] text-slate-600">ID: {{ $row->item_id }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="font-mono font-bold text-amber-300">{{ number_format((float) $row->quantity, 3) }}</span>
                                    <span class="text-slate-600 text-[10px] ml-1">{{ $row->product?->unit ?? '' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-slate-300">
                                    {{ number_format((float) $row->unit_cost, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-emerald-400">
                                    {{ number_format((float) $row->quantity * (float) $row->unit_cost, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-300 text-xs">
                                    {{ $branchName }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300">
                                        <svg class="w-3 h-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        {{ $userName }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-400 text-xs">
                                    <div>{{ $row->created_at->format('Y-m-d') }}</div>
                                    <div class="text-slate-600 text-[10px]">{{ $row->created_at->format('H:i') }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-800">
                {{ $rows->links() }}
            </div>
        @else
            <div class="py-16 text-center">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <p class="text-slate-400 font-medium">No deductions found</p>
                <p class="text-slate-600 text-xs mt-1">Try adjusting your filters or date range</p>
            </div>
        @endif
    </div>

</div>