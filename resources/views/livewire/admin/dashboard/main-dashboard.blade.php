<div class="space-y-8" wire:poll-30000="refreshAuditLogs">

    {{-- Business context switcher removed --}}

    @if(isset($isGeneralManager) && $isGeneralManager)
    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- GENERAL MANAGER DASHBOARD                                      --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    {{-- ── Today's Activity ── --}}
    <div class="flex items-center gap-3">
        <div class="h-px flex-1 bg-slate-800"></div>
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-2">Today's Activity</span>
        <div class="h-px flex-1 bg-slate-800"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-950 to-slate-900 border border-emerald-700/40 rounded-2xl p-6 group">
            <p class="text-[11px] font-bold text-emerald-400 uppercase tracking-widest">Daily Sales</p>
            <p class="text-4xl font-black text-white mt-3 tabular-nums">{{ number_format($stats['daily_sales'], 2) }}</p>
            <p class="text-xs text-slate-500 mt-2">ETB — all branches combined</p>
            <div class="mt-4 pt-4 border-t border-emerald-800/40 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs text-emerald-500 font-semibold">Live revenue tracking</span>
            </div>
            <span class="absolute right-5 top-5 text-3xl opacity-10 group-hover:opacity-20 transition">💰</span>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-sky-950 to-slate-900 border border-sky-700/40 rounded-2xl p-6 group">
            <p class="text-[11px] font-bold text-sky-400 uppercase tracking-widest">Daily Orders</p>
            <p class="text-4xl font-black text-white mt-3 tabular-nums">{{ $stats['daily_orders'] }}</p>
            <p class="text-xs text-slate-500 mt-2">New orders placed today</p>
            <div class="mt-4 pt-4 border-t border-sky-800/40">
                <a href="{{ route('admin.orders.index') }}" wire:navigate class="text-xs text-sky-500 hover:text-sky-300 font-semibold transition">Manage orders →</a>
            </div>
            <span class="absolute right-5 top-5 text-3xl opacity-10 group-hover:opacity-20 transition">🛒</span>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-violet-950 to-slate-900 border border-violet-700/40 rounded-2xl p-6 group">
            <p class="text-[11px] font-bold text-violet-400 uppercase tracking-widest">Daily Manufactured</p>
            <p class="text-4xl font-black text-white mt-3 tabular-nums">{{ number_format($stats['daily_manufactured'], 0) }}</p>
            <p class="text-xs text-slate-500 mt-2">Units produced today</p>
            <div class="mt-4 pt-4 border-t border-violet-800/40">
                <span class="text-xs text-violet-500 font-semibold">Manufacturing output</span>
            </div>
            <span class="absolute right-5 top-5 text-3xl opacity-10 group-hover:opacity-20 transition">🏭</span>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-cyan-950 to-slate-900 border border-cyan-700/40 rounded-2xl p-6 group">
            <p class="text-[11px] font-bold text-cyan-400 uppercase tracking-widest">Daily Transfers</p>
            <p class="text-4xl font-black text-white mt-3 tabular-nums">{{ $stats['daily_transfers'] ?? 0 }}</p>
            <p class="text-xs text-slate-500 mt-2">Products transferred in</p>
            <div class="mt-4 pt-4 border-t border-cyan-800/40">
                <span class="text-xs text-cyan-500 font-semibold">Stock movements</span>
            </div>
            <span class="absolute right-5 top-5 text-3xl opacity-10 group-hover:opacity-20 transition">🔄</span>
        </div>
    </div>

    {{-- ── Section label: Overall Totals ── --}}
    <div class="flex items-center gap-3">
        <div class="h-px flex-1 bg-slate-800"></div>
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-2">Overall Totals</span>
        <div class="h-px flex-1 bg-slate-800"></div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach([
            ['label'=>'Total Orders',      'value'=>$stats['total_orders'],                          'sub'=>'All time',       'dot'=>'bg-blue-400',   'icon'=>'📦'],
            ['label'=>'Total Manufactured','value'=>number_format($stats['total_manufactured'], 0),  'sub'=>'Units produced', 'dot'=>'bg-violet-400', 'icon'=>'🏭'],
            ['label'=>'Transfers',         'value'=>$stats['total_transfers'],                        'sub'=>'Stock movements','dot'=>'bg-cyan-400',   'icon'=>'🔄'],
        ] as $card)
        <div class="bg-slate-900 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 transition group">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full {{ $card['dot'] }}"></span>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest truncate">{{ $card['label'] }}</p>
            </div>
            <div class="flex items-end justify-between">
                <p class="text-2xl font-black text-white tabular-nums leading-none break-all">{{ $card['value'] }}</p>
                <span class="text-xl opacity-40 group-hover:opacity-80 transition shrink-0 ml-1">{{ $card['icon'] }}</span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ $card['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Section label: Inventory ── --}}
    <div class="flex items-center gap-3">
        <div class="h-px flex-1 bg-slate-800"></div>
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-2">Inventory</span>
        <div class="h-px flex-1 bg-slate-800"></div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 transition group">
            <div class="flex items-center gap-2 mb-3"><span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Products</p></div>
            <p class="text-3xl font-black text-white tabular-nums">{{ $stats['total_products'] }}</p>
            <p class="text-xs text-slate-600 mt-2">Distinct products</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 transition group">
            <div class="flex items-center gap-2 mb-3"><span class="w-2 h-2 rounded-full bg-teal-400"></span>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Items</p></div>
            <p class="text-3xl font-black text-white tabular-nums">{{ number_format($stats['total_items'], 0) }}</p>
            <p class="text-xs text-slate-600 mt-2">Stock quantity</p>
        </div>
        <a href="{{ route('admin.low-stock') }}" wire:navigate
           class="bg-amber-950/40 border border-amber-700/40 hover:border-amber-500/70 rounded-2xl p-5 transition group block">
            <div class="flex items-center gap-2 mb-3"><span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <p class="text-[11px] font-bold text-amber-500 uppercase tracking-widest">Low Stock</p></div>
            <p class="text-3xl font-black text-amber-400 tabular-nums">{{ $stats['low_stock'] }}</p>
            <p class="text-xs text-amber-700 mt-2 group-hover:text-amber-400 transition">View details →</p>
        </a>
        <a href="{{ route('admin.low-stock') }}?filter=out" wire:navigate
           class="bg-red-950/30 border border-red-800/40 hover:border-red-600/60 rounded-2xl p-5 transition group block">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-red-500 {{ $stats['out_of_stock'] > 0 ? 'animate-pulse' : '' }}"></span>
                <p class="text-[11px] font-bold text-red-500 uppercase tracking-widest">Out of Stock</p></div>
            <p class="text-3xl font-black text-red-400 tabular-nums">{{ $stats['out_of_stock'] }}</p>
            <p class="text-xs text-red-900 mt-2 group-hover:text-red-400 transition">
                {{ $stats['out_of_stock'] > 0 ? 'Needs attention →' : 'All stocked ✓' }}
            </p>
        </a>
    </div>

    @else
    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- SUPER ADMIN DASHBOARD                                          --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    {{-- ── Header ── --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-950 via-slate-900 to-violet-950
                border border-indigo-700/30 rounded-2xl px-7 py-6">
        
    <div>
       

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            @php
            $todayCards = [
                ['label'=>'Daily Sales',        'value'=>number_format($stats['daily_sales'] ?? 0, 2),        'sub'=>'',  'accent'=>'emerald', 'icon'=>'💰', 'glow'=>'shadow-emerald-900/30'],
                ['label'=>'Daily Orders',       'value'=>$stats['daily_orders'] ?? 0,                         'sub'=>'',  'accent'=>'sky',     'icon'=>'🛒', 'glow'=>'shadow-sky-900/30'],
                ['label'=>'Daily Manufactured', 'value'=>number_format($stats['daily_manufactured'] ?? 0, 0), 'sub'=>'',  'accent'=>'violet',  'icon'=>'🏭', 'glow'=>'shadow-violet-900/30'],
                ['label'=>'Daily Transfers',    'value'=>$stats['daily_transfers'] ?? 0,                      'sub'=>'',  'accent'=>'cyan',    'icon'=>'🔄', 'glow'=>'shadow-cyan-900/30'],
                ['label'=>'Daily Purchases',    'value'=>number_format($stats['daily_purchases'] ?? 0, 2),    'sub'=>'',  'accent'=>'amber',   'icon'=>'📥', 'glow'=>'shadow-amber-900/30'],
            ];
            @endphp

            @foreach($todayCards as $card)
            <div class="relative overflow-hidden rounded-2xl p-5 group hover:shadow-xl {{ $card['glow'] }} transition-all duration-200
                        bg-gradient-to-br from-{{ $card['accent'] }}-950/80 via-slate-900/90 to-slate-900
                        border border-{{ $card['accent'] }}-800/50 hover:border-{{ $card['accent'] }}-600/70">
                {{-- top accent line --}}
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-{{ $card['accent'] }}-500/60 to-transparent"></div>
                <div class="flex items-start justify-between mb-3">
                    <p class="text-[10px] font-extrabold text-{{ $card['accent'] }}-400/80 uppercase tracking-widest leading-tight">
                        {{ $card['label'] }}
                    </p>
                    <span class="text-lg opacity-30 group-hover:opacity-60 transition">{{ $card['icon'] }}</span>
                </div>
                <p class="text-2xl font-black text-white tabular-nums leading-none break-all">{{ $card['value'] }}</p>
                <p class="text-xs text-slate-500 mt-2 group-hover:text-slate-400 transition">{{ $card['sub'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
 
    <div>
       

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            @php
            $totalCards = [
                ['label'=>'Total Sales',        'value'=>number_format($stats['total_sales'] ?? 0, 2),        'sub'=>'',  'dot'=>'bg-emerald-400', 'border'=>'hover:border-emerald-700/50'],
                ['label'=>'Total Orders',       'value'=>$stats['total_orders'] ?? 0,                         'sub'=>'',  'dot'=>'bg-sky-400',     'border'=>'hover:border-sky-700/50'],
                ['label'=>'Total Manufactured', 'value'=>number_format($stats['total_manufactured'] ?? 0, 0), 'sub'=>'',  'dot'=>'bg-violet-400',  'border'=>'hover:border-violet-700/50'],
                ['label'=>'Total Transfers',    'value'=>$stats['total_transfers'] ?? 0,                      'sub'=>'',  'dot'=>'bg-cyan-400',    'border'=>'hover:border-cyan-700/50'],
                ['label'=>'Total Purchases',    'value'=>number_format($stats['total_purchases'] ?? 0, 2),    'sub'=>'',  'dot'=>'bg-amber-400',   'border'=>'hover:border-amber-700/50'],
            ];
            @endphp

            @foreach($totalCards as $card)
            <div class="bg-slate-900/80 border border-slate-800 {{ $card['border'] }} rounded-2xl p-5 transition group cursor-default">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-2 rounded-full {{ $card['dot'] }} shrink-0"></span>
                    <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest truncate">{{ $card['label'] }}</p>
                </div>
                <p class="text-2xl font-black text-white tabular-nums leading-none break-all">{{ $card['value'] }}</p>
                <div class="mt-3 h-px bg-slate-800 group-hover:bg-slate-700 transition"></div>
            </div>
            @endforeach
        </div>
    </div> 
    <div>
       

        {{-- KPI grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            @php
            $kpiCards = [
                ['label'=>'Products (SKUs)', 'value'=>$stats['products'],                                   'sub'=>'Items in stock: ' . number_format($stats['total_items'] ?? 0, 0), 'icon'=>'📦', 'dot'=>'bg-sky-400'],
                ['label'=>'Profit (ETB)',   'value'=>number_format($stats['profit'] ?? 0, 2),               'sub'=>'', 'icon'=>'📈', 'dot'=>'bg-blue-400'],
                ['label'=>'Pending Orders', 'value'=>$stats['pending_orders'],                              'sub'=>'', 'icon'=>'⏳', 'dot'=>'bg-orange-400'],
                ['label'=>'Raw Material Cost', 'value'=>number_format($stats['raw_material_cost'] ?? 0, 2), 'sub'=>'', 'icon'=>'📉', 'dot'=>'bg-yellow-400'],
                ['label'=>'Sold Purchase Cost', 'value'=>number_format($stats['total_sold_purchase_cost'] ?? 0, 2), 'sub'=>'', 'icon'=>'🛍️', 'dot'=>'bg-red-500'],
                ['label'=>'Total Selling',  'value'=>number_format($stats['total_selling_price'] ?? 0, 2), 'sub'=>'', 'icon'=>'💵', 'dot'=>'bg-violet-400'],
                ['label'=>'Audit Today',    'value'=>$stats['audit_logs'] ?? 0,                            'sub'=>'', 'icon'=>'🔍', 'dot'=>'bg-slate-400'],
            ];
            @endphp
            @foreach($kpiCards as $kpi)
            <div class="bg-slate-900/80 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 transition group">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full {{ $kpi['dot'] }}"></span>
                        <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest truncate">{{ $kpi['label'] }}</p>
                    </div>
                    <span class="text-base opacity-30 group-hover:opacity-60 transition">{{ $kpi['icon'] }}</span>
                </div>
                <p class="text-2xl font-black text-white tabular-nums leading-none break-all">{{ $kpi['value'] }}</p>
                @if($kpi['sub'])
                <p class="text-xs text-slate-500 mt-2">{{ $kpi['sub'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
  
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <a href="{{ route('admin.low-stock') }}" wire:navigate
               class="relative overflow-hidden bg-amber-950/40 border border-amber-800/50
                      hover:border-amber-500/70 rounded-2xl p-5 transition group block">
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-amber-500/50 to-transparent"></div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <p class="text-[10px] font-extrabold text-amber-500/80 uppercase tracking-widest">Low Stock</p>
                </div>
                <p class="text-3xl font-black text-amber-400 tabular-nums leading-none break-all">{{ $stats['low_stock'] ?? 0 }}</p>
                <p class="text-xs text-amber-800 mt-2 group-hover:text-amber-400 transition">View details →</p>
            </a>

            <a href="{{ route('admin.low-stock') }}?filter=out" wire:navigate
               class="relative overflow-hidden bg-red-950/30 border border-red-900/50
                      hover:border-red-600/60 rounded-2xl p-5 transition group block">
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-red-500/50 to-transparent"></div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 {{ ($stats['out_of_stock'] ?? 0) > 0 ? 'animate-pulse' : '' }}"></span>
                    <p class="text-[10px] font-extrabold text-red-500/80 uppercase tracking-widest">Out of Stock</p>
                </div>
                <p class="text-3xl font-black text-red-400 tabular-nums leading-none break-all">{{ $stats['out_of_stock'] ?? 0 }}</p>
                <p class="text-xs text-red-900 mt-2 group-hover:text-red-400 transition">
                    {{ ($stats['out_of_stock'] ?? 0) > 0 ? 'Needs attention →' : 'All stocked ✓' }}
                </p>
            </a>
        </div>
    </div>

    {{-- ── Zakat al-Mal Card ── --}}
    @php
        try {
            $zakatSummary = app(\App\Services\ZakatService::class)->dashboardSummary();
        } catch (\Exception $e) {
            $zakatSummary = ['has_cycle' => false, 'status' => 'no_cycle', 'zakat_due' => 0, 'due_date' => null];
        }
        $zStatus = $zakatSummary['status'];
        $zConfig = [
            'active'   => ['bg'=>'from-emerald-950/40','border'=>'border-emerald-700/40','dot'=>'bg-emerald-400',           'label'=>'Active',  'color'=>'text-emerald-400'],
            'due'      => ['bg'=>'from-amber-950/40',  'border'=>'border-amber-700/40',  'dot'=>'bg-amber-400 animate-pulse','label'=>'Due',     'color'=>'text-amber-400'],
            'overdue'  => ['bg'=>'from-red-950/40',    'border'=>'border-red-700/40',    'dot'=>'bg-red-500 animate-pulse',  'label'=>'Overdue', 'color'=>'text-red-400'],
            'paid'     => ['bg'=>'from-slate-900',     'border'=>'border-slate-700',     'dot'=>'bg-slate-400',              'label'=>'Paid',    'color'=>'text-slate-300'],
            'no_cycle' => ['bg'=>'from-slate-900',     'border'=>'border-slate-700',     'dot'=>'bg-slate-600',              'label'=>'Not Set', 'color'=>'text-slate-500'],
        ];
        $zc = $zConfig[$zStatus] ?? $zConfig['no_cycle'];
    @endphp
    <a href="{{ route('admin.zakat') }}" wire:navigate
       class="block bg-gradient-to-br {{ $zc['bg'] }} to-slate-900/80 border {{ $zc['border'] }}
              rounded-2xl p-6 hover:shadow-lg transition group">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Zakat al-Mal</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="w-2 h-2 rounded-full {{ $zc['dot'] }}"></span>
                    <span class="text-xs font-bold {{ $zc['color'] }}">{{ $zc['label'] }}</span>
                </div>
            </div>
            <span class="text-2xl opacity-60 group-hover:opacity-100 transition">☪</span>
        </div>
        @if($zakatSummary['has_cycle'])
        <p class="text-2xl font-black text-white tabular-nums break-all">
            {{ number_format($zakatSummary['zakat_due'], 2) }}
        </p>
        <p class="text-xs text-slate-500 mt-1">ETB due
            @if($zakatSummary['due_date'])
                · {{ $zakatSummary['due_date']->format('d M Y') }}
            @endif
        </p>
        @else
        <p class="text-sm text-slate-500 mt-1">Click to configure →</p>
        @endif
    </a>

    @endif {{-- end isGeneralManager --}}

</div>

