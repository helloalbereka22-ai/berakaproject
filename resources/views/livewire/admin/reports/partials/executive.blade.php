{{-- ══ EXECUTIVE DASHBOARD ═════════════════════════════════════════════════ --}}

{{-- KPI Cards row 1: revenue & orders --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4">
    @foreach([
        ['label'=>'Total Revenue',        'value'=> number_format($kpis['total_revenue'],2),     'icon'=>'💰', 'color'=>'emerald', 'sub'=>$kpis['total_orders'].' orders'],
        ['label'=>'Sold Cost',            'value'=> number_format($kpis['total_sold_cost'],2),   'icon'=>'🛒', 'color'=>'orange',  'sub'=>'COGS'],
        ['label'=>'Total Profit',          'value'=> number_format($kpis['total_profit'],2),      'icon'=>'📈', 'color'=>'blue',    'sub'=>$kpis['profit_margin_pct'].'% margin'],
        ['label'=>'Inventory Value',       'value'=> number_format($kpis['inventory_value'],2),   'icon'=>'📦', 'color'=>'amber',   'sub'=>'current stock'],
    ] as $kpi)
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition">
        <div class="flex items-start justify-between gap-2 mb-3">
            <span class="text-xl">{{ $kpi['icon'] }}</span>
            <div class="text-right flex-1 min-w-0">
                <p class="text-xs text-slate-500 mb-1">{{ $kpi['label'] }}</p>
                <p class="text-xl font-extrabold text-white truncate">{{ $kpi['value'] }}</p>
                <p class="text-xs text-{{ $kpi['color'] }}-500 mt-0.5">{{ $kpi['sub'] }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- KPI Cards row 2: alerts & manufacturing --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    @foreach([
        ['label'=>'Low Stock Items',       'value'=> $kpis['low_stock_count'],       'icon'=>'⚠️', 'color'=>'amber', 'sub'=>'below reorder level', 'tab'=>'inventory'],
        ['label'=>'Out of Stock',          'value'=> $kpis['out_of_stock_count'],    'icon'=>'🚨', 'color'=>'red',   'sub'=>'need restocking',      'tab'=>'inventory'],
        ['label'=>'Mfg Profit',            'value'=> number_format($kpis['manufacturing_profit'],2), 'icon'=>'🏭','color'=>'sky','sub'=>$kpis['oil_output_liters'].' L oil produced','tab'=>'manufacturing'],
        ['label'=>'Active Batches',        'value'=> $kpis['active_batches'],        'icon'=>'⚙️', 'color'=>'indigo','sub'=>'in production / QC',    'tab'=>'manufacturing'],
    ] as $kpi)
    <button wire:click="setTab('{{ $kpi['tab'] }}')"
        class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-{{ $kpi['color'] }}-500/40 hover:bg-slate-900/80 transition text-left group">
        <div class="flex items-start justify-between gap-2 mb-2">
            <span class="text-xl">{{ $kpi['icon'] }}</span>
            <div class="text-right flex-1 min-w-0">
                <p class="text-xs text-slate-500 mb-1">{{ $kpi['label'] }}</p>
                <p class="text-xl font-extrabold text-{{ $kpi['color'] }}-400 truncate">{{ $kpi['value'] }}</p>
                <p class="text-xs text-slate-600 mt-0.5">{{ $kpi['sub'] }}</p>
            </div>
        </div>
        <p class="text-[10px] text-{{ $kpi['color'] }}-500 opacity-0 group-hover:opacity-100 transition">View report →</p>
    </button>
    @endforeach
</div>

{{-- Drill-down: Business Performance --}}
<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
        <h3 class="font-bold text-slate-100 text-sm">Business Performance</h3>
        <button wire:click="setTab('branches')" class="text-xs text-indigo-400 hover:underline">Full branch report →</button>
    </div>
    <div class="divide-y divide-slate-800/40">
        @forelse(\App\Models\Business::withCount(['branches'])->orderBy('name')->get() as $biz)
        @php
            $bizRevenue = \App\Models\Order::where('business_id', $biz->id)
                ->whereIn('status', ['confirmed','processing','completed'])
                ->sum('total_amount');
            $bizProfit = \App\Models\ProfitLedger::where('business_id', $biz->id)->sum('profit');
        @endphp
        <div class="flex items-center gap-4 px-6 py-3 hover:bg-slate-800/30 transition">
            <div class="flex-1 min-w-0">
                <button wire:click="drillInto({{ $biz->id }}, null)"
                    class="font-semibold text-sm text-slate-200 hover:text-indigo-400 transition text-left">
                    {{ $biz->name }}
                </button>
                <p class="text-xs text-slate-500">{{ $biz->branches_count }} branch{{ $biz->branches_count !== 1 ? 'es' : '' }}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-bold text-white">{{ number_format($bizRevenue, 2) }}</p>
                <p class="text-xs {{ $bizProfit >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                    Profit: {{ number_format($bizProfit, 2) }}
                </p>
            </div>
            <button wire:click="drillInto({{ $biz->id }}, null)"
                class="shrink-0 text-slate-600 hover:text-indigo-400 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        @empty
        <div class="px-6 py-8 text-center text-slate-500 text-sm">No businesses found.</div>
        @endforelse
    </div>
</div>

{{-- If drilled into a business, show its branches --}}
@if($drillBusinessId)
<div class="bg-slate-900 border border-indigo-800/40 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
        <h3 class="font-bold text-slate-100 text-sm">
            Branches — {{ \App\Models\Business::find($drillBusinessId)?->name }}
        </h3>
        <button wire:click="clearDrill" class="text-xs text-slate-400 hover:text-white">← All businesses</button>
    </div>
    <div class="divide-y divide-slate-800/40">
        @forelse(\App\Models\Branch::where('business_id', $drillBusinessId)->orderBy('name')->get() as $branch)
        @php
            $brRev = \App\Models\OrderItem::join('orders','order_items.order_id','=','orders.id')
                ->whereIn('orders.status',['confirmed','processing','completed'])
                ->where('order_items.fulfilling_branch_id', $branch->id)
                ->sum('order_items.total_amount');
            $poRev = \App\Models\BranchSale::where('branch_id', $branch->id)->sum('total_amount');
        @endphp
        <div class="flex items-center gap-4 px-6 py-3 hover:bg-slate-800/20 transition">
            <div class="flex-1 min-w-0">
                <button wire:click="drillInto({{ $drillBusinessId }}, {{ $branch->id }})"
                    class="font-semibold text-sm text-slate-200 hover:text-indigo-400 transition text-left">
                    {{ $branch->name }}
                </button>
                <p class="text-xs text-slate-500">POS: {{ number_format($poRev, 2) }} &nbsp;·&nbsp; Orders: {{ number_format($brRev, 2) }}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-bold text-white">{{ number_format($brRev + $poRev, 2) }}</p>
                <p class="text-xs text-slate-500">total revenue</p>
            </div>
        </div>
        @empty
        <div class="px-6 py-8 text-center text-slate-500 text-sm">No branches found.</div>
        @endforelse
    </div>
</div>
@endif
