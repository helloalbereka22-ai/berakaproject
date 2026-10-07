{{-- ══ INVENTORY REPORT ════════════════════════════════════════════════════ --}}

<div class="space-y-6 pt-4">

    {{-- Summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'Total Value',    'value'=> number_format($reportData['totalValue'] ?? 0, 2), 'color'=>'emerald'],
            ['label'=>'SKUs Tracked',   'value'=> number_format(count($reportData['levels'] ?? [])),  'color'=>'blue'],
            ['label'=>'Low Stock',      'value'=> count($reportData['lowStock'] ?? []),                'color'=>'amber'],
            ['label'=>'Out of Stock',   'value'=> count($reportData['outOfStock'] ?? []),              'color'=>'red'],
        ] as $c)
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1">{{ $c['label'] }}</p>
            <p class="text-xl font-bold text-{{ $c['color'] }}-400">{{ $c['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Low stock alert --}}
    @if(count($reportData['lowStock'] ?? []) > 0)
    <div class="bg-amber-900/20 border border-amber-700/40 rounded-2xl p-4">
        <h3 class="font-bold text-amber-300 text-sm mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            Low Stock Alert ({{ count($reportData['lowStock']) }} items)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach(($reportData['lowStock'] ?? collect())->take(8) as $level)
            <div class="flex items-center justify-between bg-amber-900/20 rounded-lg px-3 py-2 text-xs">
                <span class="text-amber-200">{{ $level->item_type }}: {{ $level->item_id }}</span>
                <span class="font-bold text-amber-400">{{ $level->quantity_on_hand }} / {{ $level->reorder_level }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Stock levels table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800"><h3 class="font-bold text-slate-100 text-sm">Stock Levels</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-[10px] font-bold uppercase text-slate-500">
                    <th class="px-5 py-3 text-left">Item</th>
                    <th class="px-5 py-3 text-left">Branch</th>
                    <th class="px-5 py-3 text-right">On Hand</th>
                    <th class="px-5 py-3 text-right">Unit Cost</th>
                    <th class="px-5 py-3 text-right">Value</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse(($reportData['levels'] ?? collect())->take(50) as $level)
                    @php
                        $statusClass = $level->isOutOfStock() ? 'bg-red-900/40 text-red-400' :
                                      ($level->isLowStock()  ? 'bg-amber-900/40 text-amber-400' : 'bg-emerald-900/30 text-emerald-400');
                        $statusText  = $level->isOutOfStock() ? 'Out of Stock' : ($level->isLowStock() ? 'Low Stock' : 'OK');
                    @endphp
                    <tr class="hover:bg-slate-800/20">
                        <td class="px-5 py-3 text-slate-300 text-xs font-mono">{{ $level->item_type }}: {{ $level->item_id }}</td>
                        <td class="px-5 py-3 text-slate-400 text-xs">{{ optional(optional(optional($level->location)->branch))->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right text-slate-200 font-semibold">{{ number_format($level->quantity_on_hand, 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-400">{{ number_format($level->unit_cost, 4) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-emerald-400">{{ number_format($level->getStockValue(), 2) }}</td>
                        <td class="px-5 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusClass }}">{{ $statusText }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No stock levels found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Movement summary by type --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800"><h3 class="font-bold text-slate-100 text-sm">Movement Summary by Type</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-[10px] font-bold uppercase text-slate-500">
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-center">Direction</th>
                    <th class="px-5 py-3 text-right">Transactions</th>
                    <th class="px-5 py-3 text-right">Total Qty</th>
                    <th class="px-5 py-3 text-right">Total Cost</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($reportData['movementSummary'] ?? [] as $row)
                    <tr class="hover:bg-slate-800/20">
                        <td class="px-5 py-3 text-slate-200 capitalize">{{ str_replace('_', ' ', $row->movement_type) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $row->direction === 'in' ? 'bg-emerald-900/40 text-emerald-400' : 'bg-red-900/40 text-red-400' }}">
                                {{ strtoupper($row->direction) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-400">{{ number_format($row->count) }}</td>
                        <td class="px-5 py-3 text-right text-slate-300">{{ number_format($row->total_qty, 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-300">{{ number_format($row->total_cost, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No movements in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
