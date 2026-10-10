<div class="space-y-6">

@if($isMfrStaff)
{{-- ═══════════════════════════════════════════════════════════════
     MANUFACTURER STAFF VIEW — 4 KPI cards + Daily Manufactured
     ═══════════════════════════════════════════════════════════════ --}}
@php $s = $this->staffKpis; @endphp

<div>
    <h2 class="text-xl font-bold text-white">Dashboard</h2>
    <p class="text-slate-400 text-sm mt-0.5">{{ now()->format('l, d F Y') }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

    {{-- Total Products --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Total Products</p>
                <p class="text-4xl font-extrabold text-emerald-400 font-mono">{{ number_format($s['total_products']) }}</p>
                <p class="text-xs text-slate-500 mt-2">Manufactured items in stock</p>
            </div>
            <div class="p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
    </div>

    {{-- Today's Total Selling Price --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Today's Selling</p>
                <p class="text-4xl font-extrabold text-sky-400 font-mono">{{ number_format($s['today_selling'], 2) }}</p>
                <p class="text-xs text-slate-500 mt-2">ETB sold today</p>
            </div>
            <div class="p-3 bg-sky-500/10 rounded-xl border border-sky-500/20">
                <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-sky-500/50 to-transparent"></div>
    </div>

    {{-- Daily Manufactured - HIDDEN
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Daily Manufactured</p>
                <p class="text-4xl font-extrabold text-purple-400 font-mono">{{ number_format($this->dailyManufactured->sum('quantity'), 2) }}</p>
                <p class="text-xs text-slate-500 mt-2">Total qty produced today</p>
            </div>
            <div class="p-3 bg-purple-500/10 rounded-xl border border-purple-500/20">
                <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-purple-500/50 to-transparent"></div>
    </div>
    --}}

    {{-- Total Raw Materials --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Total Raw Materials</p>
                <p class="text-4xl font-extrabold text-amber-400 font-mono">{{ number_format($s['total_raw'], 2) }}</p>
                <p class="text-xs text-slate-500 mt-2">Available in stock</p>
            </div>
            <div class="p-3 bg-amber-500/10 rounded-xl border border-amber-500/20">
                <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500/50 to-transparent"></div>
    </div>

    {{-- Total Used Raw Materials (seed) --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative overflow-hidden">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Total Used Raw Materials</p>
                <p class="text-4xl font-extrabold text-violet-400 font-mono">{{ number_format($s['total_used_raw'], 2) }}</p>
                <p class="text-xs text-slate-500 mt-2">Seed qty deducted in production</p>
            </div>
            <div class="p-3 bg-violet-500/10 rounded-xl border border-violet-500/20">
                <svg class="w-6 h-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                </svg>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-violet-500/50 to-transparent"></div>
    </div>

</div>

{{-- Remove the table section that was previously added --}}

@else
{{-- ═══════════════════════════════════════════════════════════════
     ADMIN / SUPER-ADMIN FULL DASHBOARD
     ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-white">Manufacturing Dashboard</h2>
            <p class="text-slate-400 text-sm mt-1">Production overview, materials, and finished goods.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($isSuperAdmin && !empty($businesses))
            <select wire:change="switchBusiness($event.target.value)"
                class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Businesses</option>
                @foreach($businesses as $b)
                    <option value="{{ $b['id'] }}" {{ $selectedBusinessId == $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                @endforeach
            </select>
            @endif
            <a href="{{ route('admin.manufacturing.orders.create') }}" wire:navigate
               class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold transition">
                + New Order
            </a>
            <a href="{{ route('admin.manufacturing.batch-process') }}" wire:navigate
               class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-bold transition">
                Process Batch
            </a>
        </div>
    </div>

    @php $k = $this->kpis; @endphp

    {{-- KPI Row 1: Production output --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach([
            ['label' => "Today's Output",   'value' => number_format($k['today_output'], 2),   'sub' => 'units produced today',    'color' => 'emerald'],
            ['label' => 'Weekly Output',    'value' => number_format($k['weekly_output'], 2),  'sub' => 'units this week',          'color' => 'blue'],
            ['label' => 'Monthly Output',   'value' => number_format($k['monthly_output'], 2), 'sub' => 'units this month',         'color' => 'violet'],
        ] as $card)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
            <p class="text-sm font-semibold text-slate-400">{{ $card['label'] }}</p>
            <p class="text-3xl font-bold text-{{ $card['color'] }}-400 mt-1">{{ $card['value'] }}</p>
            <p class="text-xs text-slate-600 mt-1">{{ $card['sub'] }}</p>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-{{ $card['color'] }}-500/50 to-transparent"></div>
        </div>
        @endforeach
    </div>

    {{-- KPI Row 2: Batches + materials --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            ['label' => 'Active Batches',     'value' => $k['active_batches'],          'color' => 'amber'],
            ['label' => 'Completed Batches',  'value' => $k['completed_batches'],       'color' => 'emerald'],
            ['label' => 'FG Available',       'value' => number_format($k['fg_available'], 0), 'color' => 'sky'],
            ['label' => 'Low Stock Items',    'value' => $k['low_stock_count'],         'color' => 'red'],
        ] as $card)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $card['label'] }}</p>
            <p class="text-2xl font-bold text-{{ $card['color'] }}-400 mt-1">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- KPI Row 3: Consumption --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach([
            ['label' => 'Raw Consumed (Month)',   'value' => number_format($k['monthly_raw_consumed_kg'], 2) . ' kg',   'color' => 'amber'],
            ['label' => 'Packing Consumed (Month)','value' => number_format($k['monthly_pack_consumed'], 0) . ' pcs',  'color' => 'blue'],
            ['label' => 'Consumption Cost (Month)','value' => number_format($k['monthly_consump_cost'], 2) . ' ETB',   'color' => 'violet'],
        ] as $card)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $card['label'] }}</p>
            <p class="text-xl font-bold text-{{ $card['color'] }}-400 mt-1">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Raw Material Stock --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">Raw Materials</h3>
                <a href="{{ route('admin.materials.raw') }}" wire:navigate class="text-xs text-indigo-400 hover:text-indigo-300">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800/60 bg-slate-800/20">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Material</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500">In Stock</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500">Reorder At</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse($this->rawMaterialStock->take(10) as $p)
                        @php $stock = $p->current_stock; $low = $p->reorder_point > 0 && $stock <= $p->reorder_point; @endphp
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="px-4 py-2.5 font-medium text-white text-xs">{{ $p->name }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs {{ $low ? 'text-red-400 font-bold' : 'text-slate-200' }}">
                                {{ number_format($stock, 2) }} {{ $p->unit }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-500 text-xs">{{ $p->reorder_point ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-xs">
                                @if($stock <= 0)
                                    <span class="text-red-400 font-semibold">Out of Stock</span>
                                @elseif($low)
                                    <span class="text-amber-400 font-semibold">Low Stock</span>
                                @else
                                    <span class="text-emerald-400">OK</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-600 text-xs">No raw materials configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Packaging Stock --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">Packaging Materials</h3>
                <a href="{{ route('admin.materials.packaging') }}" wire:navigate class="text-xs text-indigo-400 hover:text-indigo-300">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800/60 bg-slate-800/20">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Material</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500">In Stock</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500">Reorder At</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse($this->packagingStock->take(10) as $p)
                        @php $stock = $p->current_stock; $low = $p->reorder_point > 0 && $stock <= $p->reorder_point; @endphp
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="px-4 py-2.5 font-medium text-white text-xs">{{ $p->name }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs {{ $low ? 'text-red-400 font-bold' : 'text-slate-200' }}">
                                {{ number_format($stock, 0) }} {{ $p->unit }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-500 text-xs">{{ $p->reorder_point ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-xs">
                                @if($stock <= 0)
                                    <span class="text-red-400 font-semibold">Out of Stock</span>
                                @elseif($low)
                                    <span class="text-amber-400 font-semibold">Low Stock</span>
                                @else
                                    <span class="text-emerald-400">OK</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-600 text-xs">No packaging materials configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Daily Manufactured Items --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">Daily Manufactured Items</h3>
                <p class="text-xs text-slate-500 mt-0.5">Today's production with per-batch seed usage</p>
            </div>
            <span class="text-xs text-slate-500">{{ $this->dailyManufactured->count() }} item(s)</span>
        </div>
        @if($this->dailyManufactured->isEmpty())
            <div class="py-12 text-center">
                <div class="text-4xl mb-3">🏭</div>
                <p class="text-slate-400 text-sm">No items manufactured today</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800/60 bg-slate-800/20">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Product</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Category</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500">Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Seed Used</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500">Seed Qty</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500">Unit Price</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @foreach($this->dailyManufactured as $item)
                    <tr class="hover:bg-slate-800/20 transition">
                        <td class="px-5 py-3 font-medium text-white text-xs">{{ $item['item_name'] }}</td>
                        <td class="px-5 py-3 text-xs text-slate-400">{{ $item['category_name'] }}</td>
                        <td class="px-5 py-3 text-right font-mono text-emerald-400 font-semibold text-xs">
                            {{ number_format($item['quantity'], 2) }} {{ $item['unit'] }}
                        </td>
                        <td class="px-5 py-3 text-xs text-amber-300 font-semibold">{{ $item['seed_name'] }}</td>
                        <td class="px-5 py-3 text-right font-mono text-amber-400 text-xs">
                            @if($item['seed_qty_used'] > 0)
                                {{ number_format($item['seed_qty_used'], 2) }} {{ $item['seed_unit'] }}
                            @else
                                <span class="text-slate-600">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-sky-300 text-xs">{{ number_format($item['selling_price'], 2) }} ETB</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ \Carbon\Carbon::parse($item['created_at'])->format('H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                        <td class="px-5 py-3 text-slate-400" colspan="2">Daily Total</td>
                        <td class="px-5 py-3 text-right font-mono text-emerald-400">
                            {{ number_format($this->dailyManufactured->sum('quantity'), 2) }}
                        </td>
                        <td class="px-5 py-3"></td>
                        <td class="px-5 py-3 text-right font-mono text-amber-400">
                            {{ number_format($this->dailyManufactured->sum('seed_qty_used'), 2) }}
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-sky-300">
                            {{ number_format($this->dailyManufactured->sum(fn($i) => $i['quantity'] * $i['selling_price']), 2) }} ETB
                        </td>
                        <td class="px-5 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Recent Batches --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide">Recent Batches</h3>
            <a href="{{ route('admin.manufacturing.batch-process') }}" wire:navigate class="text-xs text-indigo-400 hover:text-indigo-300">Process →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800/60 bg-slate-800/20">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Batch</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Product</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500">Output</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Started</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($this->recentBatches as $b)
                    @php
                        $sc = ['open'=>'amber','in_qc'=>'sky','approved'=>'blue','packed'=>'violet','completed'=>'emerald','cancelled'=>'red'];
                        $c = $sc[$b->status] ?? 'slate';
                    @endphp
                    <tr class="hover:bg-slate-800/20 transition">
                        <td class="px-5 py-3 font-mono text-indigo-300 text-xs">{{ $b->batch_number }}</td>
                        <td class="px-5 py-3 font-medium text-white text-xs">{{ optional(optional($b->manufacturingOrder)->product)->name }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">
                            {{ $b->oil_quantity_produced > 0 ? number_format($b->oil_quantity_produced, 2).' '.$b->oil_unit : '—' }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $c }}-500/10 text-{{ $c }}-400 border border-{{ $c }}-500/20">
                                {{ ucfirst($b->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $b->started_at?->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            @if(!in_array($b->status, ['completed','cancelled']))
                            <a href="{{ route('admin.manufacturing.batch-process', ['batchId' => $b->id]) }}" wire:navigate
                               class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">Continue →</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-600 text-sm">No batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Material Purchases --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide">Recent Material Purchases</h3>
            <a href="{{ route('admin.purchases.create') }}" wire:navigate class="text-xs text-indigo-400 hover:text-indigo-300">+ New Purchase</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800/60 bg-slate-800/20">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Material</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Type</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500">Qty</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500">Total</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($this->recentPurchases as $pur)
                    <tr class="hover:bg-slate-800/20 transition">
                        <td class="px-5 py-3 font-medium text-white text-xs">{{ optional($pur->product)->name }}</td>
                        <td class="px-5 py-3 text-xs">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $pur->purchase_type === 'raw_material' ? 'bg-amber-500/10 text-amber-400' : 'bg-blue-500/10 text-blue-400' }}">
                                {{ ucfirst(str_replace('_',' ',$pur->purchase_type)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">{{ number_format($pur->quantity, 2) }} {{ $pur->unit }}</td>
                        <td class="px-5 py-3 text-right font-bold font-mono text-white text-xs">{{ number_format($pur->total_price, 2) }} ETB</td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $pur->purchase_date?->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-600 text-sm">No purchases recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endif
{{-- end @if($isMfrStaff) --}}

</div>
