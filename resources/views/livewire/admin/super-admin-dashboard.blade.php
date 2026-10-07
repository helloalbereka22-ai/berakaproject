<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════════════
         HEADER + FILTERS
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Business Overview</h2>
            <p class="text-slate-400 text-sm mt-1">Global view across all businesses, branches, and operations.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Business selector --}}
            <select wire:model.live="selectedBusinessId"
                class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none focus:border-indigo-500 min-w-[160px]">
                <option value="">All Businesses</option>
                @foreach($businesses as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </select>

            {{-- Period tabs --}}
            @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'custom' => 'Custom'] as $val => $label)
            <button wire:click="$set('period', '{{ $val }}')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition
                    {{ $period === $val
                        ? 'bg-indigo-600 text-white'
                        : 'bg-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-700' }}">
                {{ $label }}
            </button>
            @endforeach

            {{-- Refresh button --}}
            <button wire:click="refreshDashboard"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>

            @if($period === 'custom')
            <input wire:model="dateFrom" type="date"
                class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-200 outline-none">
            <input wire:model="dateTo" type="date"
                class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-200 outline-none">
            <button wire:click="applyFilter"
                class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition">
                Apply
            </button>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         DAILY SALES & MANUFACTURED HIGHLIGHT
    ═══════════════════════════════════════════════════════════════════ --}}
    @php $ps = $this->periodSales; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Daily Sales --}}
        <div class="bg-gradient-to-r from-emerald-900/30 to-emerald-800/20 border border-emerald-700/50 rounded-2xl p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-400/80 uppercase tracking-widest">Daily Sales</p>
                    <p class="text-4xl font-bold text-emerald-300 mt-2">
                        💰 {{ number_format($ps['today_revenue'], 2) }}
                    </p>
                    <p class="text-sm text-slate-400 mt-2">
                        Today's Total Sales | {{ $ps['today_orders'] }} orders
                    </p>
                </div>
                <div class="text-6xl opacity-20 text-emerald-400">📊</div>
            </div>
        </div>

        {{-- Daily Manufactured --}}
        <div class="bg-gradient-to-r from-violet-900/30 to-violet-800/20 border border-violet-700/50 rounded-2xl p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-violet-400/80 uppercase tracking-widest">Daily Manufactured</p>
                    <p class="text-4xl font-bold text-violet-300 mt-2">
                        🏭 {{ number_format($this->dailyManufactured->sum('quantity'), 2) }}
                    </p>
                    <p class="text-sm text-slate-400 mt-2">
                        Today's Production | {{ $this->dailyManufactured->count() }} items
                    </p>
                </div>
                <div class="text-6xl opacity-20 text-violet-400">⚙️</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         PERIOD QUICK-STATS
    ═══════════════════════════════════════════════════════════════════ --}}
    @php $ps = $this->periodSales; @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach([
            ['label' => "Today Orders",   'value' => $ps['today_orders'],    'sub' => number_format($ps['today_revenue'],2).' ETB',   'color' => 'amber'],
            ['label' => "Today Revenue",  'value' => number_format($ps['today_revenue'],2).' ETB', 'sub' => '', 'color' => 'emerald'],
            ['label' => "Week Orders",    'value' => $ps['weekly_orders'],   'sub' => number_format($ps['weekly_revenue'],2).' ETB',  'color' => 'sky'],
            ['label' => "Week Revenue",   'value' => number_format($ps['weekly_revenue'],2).' ETB','sub' => '', 'color' => 'blue'],
            ['label' => "Month Orders",   'value' => $ps['monthly_orders'],  'sub' => number_format($ps['monthly_revenue'],2).' ETB', 'color' => 'violet'],
            ['label' => "Month Revenue",  'value' => number_format($ps['monthly_revenue'],2).' ETB','sub' => '', 'color' => 'indigo'],
        ] as $card)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $card['label'] }}</p>
            <p class="text-lg font-bold text-{{ $card['color'] }}-400 mt-1 leading-tight">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         BUSINESS SUMMARY (all-time)
    ═══════════════════════════════════════════════════════════════════ --}}
    @php $bs = $this->businessSummary; @endphp
    <div>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Business Summary (All Time)</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-9 gap-3">
            @foreach([
                ['label' => 'Total Products',     'value' => $bs['total_products'],                    'color' => 'slate'],
                ['label' => 'Purchased',          'value' => number_format($bs['total_purchased'],0),  'color' => 'blue'],
                ['label' => 'Produced',           'value' => number_format($bs['total_produced'],0),   'color' => 'violet'],
                ['label' => 'Total Sold',         'value' => number_format($bs['total_sold'],0),       'color' => 'emerald'],
                ['label' => 'Total Orders',       'value' => number_format($bs['total_orders']),       'color' => 'sky'],
                ['label' => 'Total Revenue',      'value' => number_format($bs['total_revenue'],2),    'color' => 'amber'],
                ['label' => 'Sold Cost',          'value' => number_format($bs['total_sold_cost'],2),  'color' => 'orange'],
                ['label' => 'Sold Purchase Cost', 'value' => number_format($bs['total_sold_purchase_cost'],2), 'color' => 'red'],
                ['label' => 'Total Profit',       'value' => number_format($bs['total_profit'],2),     'color' => 'green'],
            ] as $card)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $card['label'] }}</p>
                <p class="text-lg font-bold text-{{ $card['color'] }}-400 mt-1">{{ $card['value'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SALES REPORT
    ═══════════════════════════════════════════════════════════════════ --}}
    @php $sr = $this->salesReport; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Sales summary + pending --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide">Sales — Period</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    ['label' => 'Completed Orders', 'value' => $sr['order_count'],              'color' => 'emerald'],
                    ['label' => 'Revenue',           'value' => number_format($sr['revenue'],2).' ETB', 'color' => 'amber'],
                    ['label' => 'Sold Cost',         'value' => number_format($this->periodSoldCost,2).' ETB', 'color' => 'orange'],
                    ['label' => 'Discounts Given',   'value' => number_format($sr['discounts'],2).' ETB', 'color' => 'slate'],
                ] as $c)
                <div class="bg-slate-800/50 rounded-xl p-3">
                    <p class="text-xs text-slate-500">{{ $c['label'] }}</p>
                    <p class="text-xl font-bold text-{{ $c['color'] }}-400 mt-0.5">{{ $c['value'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Top Products --}}
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Top Products Sold</p>
                @forelse($sr['top_products'] as $p)
                <div class="flex items-center justify-between py-1.5 border-b border-slate-800/60">
                    <div>
                        <span class="text-xs font-semibold text-white">{{ $p->name }}</span>
                        <span class="text-xs text-slate-600 ml-1">({{ $p->type }})</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-mono text-emerald-400">{{ number_format($p->qty_sold,0) }} sold</span>
                        <span class="text-xs text-slate-500 ml-2">{{ number_format($p->revenue,2) }} ETB</span>
                    </div>
                </div>
                @empty
                <p class="text-slate-600 text-xs py-2">No sales in this period.</p>
                @endforelse
            </div>
        </div>

        {{-- Sales by branch --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-3">Sales by Branch — Period</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-800/60">
                            <th class="text-left py-2 text-slate-500 font-semibold uppercase">Branch</th>
                            <th class="text-right py-2 text-slate-500 font-semibold uppercase">Orders</th>
                            <th class="text-right py-2 text-slate-500 font-semibold uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse($sr['by_branch'] as $row)
                        <tr>
                            <td class="py-2 text-slate-200">{{ optional($row->branch)->name ?? 'Unassigned' }}</td>
                            <td class="py-2 text-right font-mono text-slate-300">{{ $row->order_count }}</td>
                            <td class="py-2 text-right font-bold font-mono text-emerald-400">{{ number_format($row->revenue,2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="py-6 text-center text-slate-600">No sales data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         BRANCH SUMMARIES
    ═══════════════════════════════════════════════════════════════════ --}}
    <div>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Branch Inventory & Sales Summary</h3>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-800/60 bg-slate-800/20">
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Branch</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">In Stock</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Purchased</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Produced</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Transfer In</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Transfer Out</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Sold (All)</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Period Sales</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Period Orders</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Products</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider text-amber-500">Low Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse($this->branchSummaries as $b)
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-white">{{ $b->name }}</p>
                                <p class="text-slate-600 text-xs">{{ $b->business_name }}</p>
                            </td>
                            <td class="px-4 py-3 text-right font-mono {{ $b->available_qty <= 0 ? 'text-red-400' : 'text-emerald-400' }} font-bold">
                                {{ number_format($b->available_qty, 0) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-300">{{ number_format($b->purchased_qty, 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-violet-400">{{ number_format($b->produced_qty, 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sky-400">{{ number_format($b->transfer_in, 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-400">{{ number_format($b->transfer_out, 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-300">{{ number_format($b->sold_qty, 0) }}</td>
                            <td class="px-4 py-3 text-right font-bold font-mono text-amber-400">{{ number_format($b->period_sales, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-300">{{ $b->period_orders }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-300">{{ $b->products_in_stock }}</td>
                            <td class="px-4 py-3 text-right font-bold {{ $b->low_stock_count > 0 ? 'text-amber-400' : 'text-slate-600' }}">
                                {{ $b->low_stock_count }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="px-4 py-10 text-center text-slate-600">No branches found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SOLD COST HIGHLIGHT (All Time)
    ═══════════════════════════════════════════════════════════════════ --}}
    @php $bs = $this->businessSummary; @endphp
    <div class="bg-gradient-to-r from-orange-900/30 to-orange-800/20 border border-orange-700/50 rounded-2xl p-6 cursor-pointer hover:border-orange-600/80 transition group" 
         wire:click="toggleSoldCostDetails">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold text-orange-400/80 uppercase tracking-widest">Sold Cost (COGS)</p>
                <p class="text-4xl font-bold text-orange-300 mt-2">
                    🛒 {{ number_format($bs['total_sold_cost'], 2) }}
                </p>
                <p class="text-sm text-slate-400 mt-2">
                    Total Cost of Goods Sold | All Branches
                </p>
                <p class="text-xs text-orange-500/60 mt-3 group-hover:text-orange-400 transition">
                    Click to view breakdown →
                </p>
            </div>
            <div class="text-6xl opacity-20 text-orange-400 group-hover:opacity-30 transition">💼</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         TOTAL SOLD PURCHASE COST HIGHLIGHT (All Time, All Channels)
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-gradient-to-r from-red-900/30 to-red-800/20 border border-red-700/50 rounded-2xl p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold text-red-400/80 uppercase tracking-widest">Total Sold Purchase Cost</p>
                <p class="text-4xl font-bold text-red-300 mt-2">
                    🛍️ {{ number_format($this->totalSoldPurchaseCost, 2) }}
                </p>
                <p class="text-sm text-slate-400 mt-2">
                    All Sales Channels | Guest + Customer + POS | All Time
                </p>
            </div>
            <div class="text-6xl opacity-20 text-red-400">📊</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         PURCHASE REPORT
    ═══════════════════════════════════════════════════════════════════ --}}
    @php $pr = $this->purchaseReport; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Purchase Report — Period</h3>

            {{-- Totals --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-slate-800/50 rounded-xl p-3">
                    <p class="text-xs text-slate-500">Transactions</p>
                    <p class="text-xl font-bold text-white mt-0.5">{{ number_format($pr['total_count']) }}</p>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-3">
                    <p class="text-xs text-slate-500">Total Qty</p>
                    <p class="text-xl font-bold text-blue-400 mt-0.5">{{ number_format($pr['total_qty'],0) }}</p>
                </div>
                <div class="bg-slate-800/50 rounded-xl p-3">
                    <p class="text-xs text-slate-500">Total Value</p>
                    <p class="text-xl font-bold text-amber-400 mt-0.5">{{ number_format($pr['total_value'],0) }}</p>
                </div>
            </div>

            {{-- By type --}}
            @foreach(['product' => ['Retail Products','blue'], 'raw_material' => ['Raw Materials','amber'], 'packing_material' => ['Packaging','violet']] as $type => [$label, $color])
            @php $row = $pr['by_type'][$type] ?? null; @endphp
            <div class="flex items-center justify-between py-2 border-b border-slate-800/60">
                <span class="text-xs font-semibold text-{{ $color }}-400">{{ $label }}</span>
                <div class="text-right">
                    <span class="text-xs font-mono text-slate-300">{{ number_format($row->total_qty ?? 0,0) }} units</span>
                    <span class="text-xs font-mono text-{{ $color }}-400 ml-3">{{ number_format($row->total_value ?? 0,2) }} ETB</span>
                </div>
            </div>
            @endforeach

            {{-- Top purchased --}}
            <p class="text-xs font-semibold text-slate-500 uppercase mt-4 mb-2">Top Purchased</p>
            @forelse($pr['top_products'] as $p)
            <div class="flex justify-between py-1.5 border-b border-slate-800/40">
                <span class="text-xs text-slate-200">{{ $p->name }}</span>
                <span class="text-xs font-mono text-blue-400">{{ number_format($p->total_qty,0) }} {{ $p->unit }}</span>
            </div>
            @empty
            <p class="text-slate-600 text-xs py-2">No purchases in this period.</p>
            @endforelse
        </div>

        {{-- Manufacturing Report --}}
        @php $mr = $this->manufacturingReport; @endphp
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Manufacturing Report — Period</h3>

            <div class="grid grid-cols-3 gap-3 mb-4">
                @foreach([
                    ['Batches',       $mr['batch_count'],                       'text-white'],
                    ['Produced',      number_format($mr['total_produced'],2),    'text-emerald-400'],
                    ['Profit',        number_format($mr['profit'],2).' ETB',     'text-amber-400'],
                    ['Raw Cost',      number_format($mr['raw_cost'],2).' ETB',   'text-orange-400'],
                    ['Pack Cost',     number_format($mr['pack_cost'],2).' ETB',  'text-violet-400'],
                    ['Revenue',       number_format($mr['revenue'],2).' ETB',    'text-sky-400'],
                ] as [$label, $val, $cls])
                <div class="bg-slate-800/50 rounded-xl p-3">
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="text-base font-bold {{ $cls }} mt-0.5">{{ $val }}</p>
                </div>
                @endforeach
            </div>

            {{-- Raw consumed --}}
            @if($mr['raw_consumed']->count())
            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Raw Material Consumed</p>
            @foreach($mr['raw_consumed'] as $c)
            <div class="flex justify-between py-1.5 border-b border-slate-800/40">
                <span class="text-xs text-slate-200">{{ $c->name }}</span>
                <span class="text-xs font-mono text-amber-400">{{ number_format($c->total_consumed,2) }} {{ $c->unit }}</span>
            </div>
            @endforeach
            @endif

            {{-- Packing consumed --}}
            @if($mr['pack_consumed']->count())
            <p class="text-xs font-semibold text-slate-500 uppercase mt-3 mb-2">Packaging Consumed</p>
            @foreach($mr['pack_consumed'] as $c)
            <div class="flex justify-between py-1.5 border-b border-slate-800/40">
                <span class="text-xs text-slate-200">{{ $c->name }}</span>
                <span class="text-xs font-mono text-violet-400">{{ number_format($c->total_consumed,0) }} {{ $c->unit }}</span>
            </div>
            @endforeach
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         INVENTORY AUDIT TABLE
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide">Inventory Audit</h3>
            <p class="text-xs text-slate-500">Purchased + Produced + Transfer In − Sold − Consumed − Transfer Out = Available</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-800/60 bg-slate-800/20">
                        <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="text-right px-4 py-3 font-semibold text-blue-400 uppercase tracking-wider">Purchased</th>
                        <th class="text-right px-4 py-3 font-semibold text-violet-400 uppercase tracking-wider">Produced</th>
                        <th class="text-right px-4 py-3 font-semibold text-sky-400 uppercase tracking-wider">Trans. In</th>
                        <th class="text-right px-4 py-3 font-semibold text-red-400 uppercase tracking-wider">Sold</th>
                        <th class="text-right px-4 py-3 font-semibold text-orange-400 uppercase tracking-wider">Consumed</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-400 uppercase tracking-wider">Trans. Out</th>
                        <th class="text-right px-4 py-3 font-semibold text-emerald-400 uppercase tracking-wider">Available</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($this->inventoryAudit as $row)
                    @php
                        $typeColors = ['raw_material'=>'amber','packing_material'=>'violet','finished_product'=>'emerald','book'=>'blue','stationery'=>'sky','fashion'=>'pink','general'=>'slate'];
                        $tc = $typeColors[$row->product_type] ?? 'slate';
                        $available = (float) $row->available;
                    @endphp
                    <tr class="hover:bg-slate-800/20 transition {{ $available < 0 ? 'bg-red-500/5' : '' }}">
                        <td class="px-4 py-2.5 font-medium text-white">{{ $row->product_name }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-semibold
                                bg-{{ $tc }}-500/10 text-{{ $tc }}-400">
                                {{ ucfirst(str_replace('_',' ',$row->product_type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-blue-400">{{ number_format($row->purchased,2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-violet-400">{{ number_format($row->produced,2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-sky-400">{{ number_format($row->transferred_in,2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-red-400">{{ number_format($row->sold,2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-orange-400">{{ number_format($row->consumed,2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-slate-400">{{ number_format($row->transferred_out,2) }}</td>
                        <td class="px-4 py-2.5 text-right font-bold font-mono {{ $available > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ number_format($available,2) }}
                            <span class="text-slate-600 font-normal ml-0.5">{{ $row->unit }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-slate-600">No inventory movements found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SOLD COST DETAILS MODAL
    ═══════════════════════════════════════════════════════════════════ --}}
    @if($showSoldCostDetails)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:click="$el.closest('div').remove()">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"></div>
        
        {{-- Modal --}}
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden flex flex-col shadow-2xl"
             @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50 bg-slate-800/30">
                <div>
                    <h3 class="text-lg font-bold text-white">Sold Cost Breakdown</h3>
                    <p class="text-xs text-slate-400 mt-1">Sum of (Quantity Sold × Purchase Price) by Product</p>
                </div>
                <button wire:click="toggleSoldCostDetails" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="overflow-y-auto flex-1 px-6 py-4">
                @php $details = $this->soldCostDetails; @endphp
                
                @if($details->count() > 0)
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-slate-800/50">
                        <tr class="border-b border-slate-700/50">
                            <th class="text-left py-3 px-3 font-semibold text-slate-400 uppercase text-xs">Product Name</th>
                            <th class="text-right py-3 px-3 font-semibold text-slate-400 uppercase text-xs">Qty Sold</th>
                            <th class="text-right py-3 px-3 font-semibold text-slate-400 uppercase text-xs">Avg Cost</th>
                            <th class="text-right py-3 px-3 font-semibold text-slate-400 uppercase text-xs">Total Cost</th>
                            <th class="text-left py-3 px-3 font-semibold text-slate-400 uppercase text-xs">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        @foreach($details as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-3 text-slate-200 font-medium">
                                {{ $item['name'] }}
                            </td>
                            <td class="py-3 px-3 text-right text-slate-300 font-mono">
                                {{ number_format($item['qty'], 2) }}
                            </td>
                            <td class="py-3 px-3 text-right text-slate-300 font-mono">
                                {{ number_format($item['avg_cost'], 2) }} ETB
                            </td>
                            <td class="py-3 px-3 text-right text-orange-400 font-bold font-mono">
                                {{ number_format($item['cost'], 2) }} ETB
                            </td>
                            <td class="py-3 px-3 text-slate-400 text-xs">
                                <span class="inline-block px-2 py-1 rounded {{ $item['source'] === 'Orders' ? 'bg-emerald-500/20 text-emerald-400' : ($item['source'] === 'POS Sales' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400') }}">
                                    {{ $item['source'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-800/50 border-t border-slate-700/50 font-bold">
                        <tr>
                            <td class="py-3 px-3 text-slate-200">TOTAL</td>
                            <td class="py-3 px-3 text-right text-slate-300 font-mono">
                                {{ number_format($details->sum('qty'), 2) }}
                            </td>
                            <td class="py-3 px-3 text-right text-slate-300 font-mono">
                                —
                            </td>
                            <td class="py-3 px-3 text-right text-orange-400 font-mono">
                                {{ number_format($details->sum('cost'), 2) }} ETB
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <div class="py-12 text-center">
                    <p class="text-slate-400">No sold items found for the selected period.</p>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-700/50 bg-slate-800/20 flex items-center justify-end gap-3">
                <button wire:click="toggleSoldCostDetails"
                    class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 text-sm font-semibold transition">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
