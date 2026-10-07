<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4 flex-wrap">
        <a href="{{ route('admin.dashboard') }}" wire:navigate
           class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            @if($filter === 'out')
            <h2 class="text-2xl font-bold text-white">🚫 Out of Stock Items</h2>
            <p class="text-sm text-slate-400 mt-0.5">Items with available quantity ≤ <strong class="text-red-400">0</strong></p>
            @else
            <h2 class="text-2xl font-bold text-white">⚠️ Low Stock Items</h2>
            <p class="text-sm text-slate-400 mt-0.5">Items with available quantity ≤ <strong class="text-amber-400">{{ $threshold }}</strong></p>
            @endif
        </div>
        <span class="ml-auto px-3 py-1.5 {{ $filter === 'out' ? 'bg-red-500/20 text-red-300 border-red-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30' }} text-sm font-black rounded-full border">
            {{ $items->count() }} items
        </span>
    </div>

    {{-- Threshold control + Search --}}
    <div class="flex flex-wrap items-center gap-3">

        {{-- Search --}}
        <div class="relative flex-1 min-w-[200px] max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Search by item, branch, category..."
                   class="w-full pl-9 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
        </div>

        {{-- Threshold setter (only for low stock) --}}
        @if($filter !== 'out')
        <div class="flex items-center gap-2 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2">
            <span class="text-xs text-slate-400 whitespace-nowrap">Threshold ≤</span>
            <input wire:model.live="threshold" type="number" min="1" step="1"
                   class="w-20 bg-transparent text-amber-400 font-bold text-sm text-center outline-none border-b border-amber-500/40 focus:border-amber-400">
            <button wire:click="saveThreshold"
                    class="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-black text-xs font-bold rounded-lg transition">
                Save
            </button>
        </div>
        @endif

    </div>

    {{-- Success toast --}}
    <div x-data="{ show: false }"
         x-on:threshold-saved.window="show = true; setTimeout(() => show = false, 2500)"
         x-show="show" x-transition
         class="px-4 py-2.5 bg-green-500/20 border border-green-500/40 text-green-400 text-sm rounded-xl">
        ✅ Threshold saved successfully.
    </div>

    {{-- Table --}}
    <div class="bg-slate-900 border {{ $filter === 'out' ? 'border-red-500/30' : 'border-amber-500/30' }} rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 {{ $filter === 'out' ? 'bg-red-500/5' : 'bg-amber-500/5' }} flex items-center justify-between">
            @if($filter === 'out')
            <h3 class="font-bold text-red-300 text-sm">🚫 Out of Stock — Qty ≤ 0</h3>
            @else
            <h3 class="font-bold text-amber-300 text-sm">⚠️ Low Stock — Threshold: {{ $threshold }} units</h3>
            @endif
            <span class="text-xs text-slate-500">{{ $items->count() }} item(s) below threshold</span>
        </div>

        @if($items->isEmpty())
            <div class="py-16 text-center">
                <div class="text-5xl mb-4">{{ $filter === 'out' ? '✅' : '✅' }}</div>
                @if($filter === 'out')
                <p class="text-slate-400 font-medium text-sm">No out of stock items found</p>
                <p class="text-slate-600 text-xs mt-1">All items have positive quantity</p>
                @else
                <p class="text-slate-400 font-medium text-sm">No low stock items found</p>
                <p class="text-slate-600 text-xs mt-1">All items have quantity above {{ $threshold }}</p>
                @endif
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-800/40 border-b border-slate-700 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">#</th>
                        <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-left px-5 py-3.5 font-bold">Item</th>
                        <th class="text-right px-5 py-3.5 font-bold">Available Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @foreach($items as $i => $row)
                    <tr class="hover:{{ $filter === 'out' ? 'bg-red-500/5' : 'bg-amber-500/5' }} transition">
                        <td class="px-5 py-3.5 text-xs text-slate-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-400">{{ $row->branch ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-400">{{ $row->category ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="font-semibold text-white">{{ $row->item }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @php 
                            $qtyColor = $row->net_qty <= 0 ? 'text-red-400' : ($row->net_qty <= $threshold ? 'text-amber-400' : 'text-green-400');
                            $pct = $threshold > 0 ? min(100, ($row->net_qty / $threshold) * 100) : 0; 
                            @endphp
                            <div class="flex items-center justify-end gap-2">
                                {{-- mini progress bar --}}
                                <div class="w-16 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $row->net_qty <= 0 ? 'bg-red-500' : ($pct < 60 ? 'bg-amber-500' : 'bg-green-500') }}"
                                         style="width: {{ $pct > 0 ? $pct : 5 }}%"></div>
                                </div>
                                <span class="font-black font-mono {{ $qtyColor }} text-base">{{ number_format($row->net_qty, 2) }}</span>
                                <span class="text-slate-500 text-[10px]">{{ $row->unit }}</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/50">
                        <td colspan="4" class="px-5 py-3 text-xs font-bold text-slate-400 uppercase">
                            Total {{ $filter === 'out' ? 'Out of Stock Items' : 'Low Stock Items' }}
                        </td>
                        <td class="px-5 py-3 text-right font-black {{ $filter === 'out' ? 'text-red-400' : 'text-amber-400' }} text-lg">
                            {{ $items->count() }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
