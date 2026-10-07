<div class="space-y-6" x-data="{ exportOpen: false }">

    @php
        $isMfrStaff = auth()->user()->can('manufacturing.produce')
            && !auth()->user()->can('business.update')
            && !auth()->user()->isSuperAdmin();
    @endphp

    {{-- ══ Page header ════════════════════════════════════════════════════ --}}
    @if(!$isMfrStaff)
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-100">Reports & Analytics</h1>
            <p class="text-slate-400 text-sm mt-0.5">
                @if($drillBusinessId || $drillBranchId)
                    <button wire:click="clearDrill" class="text-indigo-400 hover:underline text-xs mr-2">← All Businesses</button>
                    @if($drillBusinessId)
                    <span class="text-slate-300 text-xs font-semibold">
                        {{ \App\Models\Business::find($drillBusinessId)?->name ?? '' }}
                    </span>
                    @endif
                    @if($drillBranchId)
                    <span class="text-slate-500 text-xs"> / </span>
                    <span class="text-slate-300 text-xs font-semibold">
                        {{ \App\Models\Branch::find($drillBranchId)?->name ?? '' }}
                    </span>
                    @endif
                @else
                    Drill-down from platform → business → branch → product
                @endif
            </p>
        </div>
        {{-- Export button --}}
        @if($activeTab !== 'executive' && $activeTab !== 'manufacturer_report')
        <button wire:click="exportCsv"
            wire:loading.attr="disabled"
            wire:target="exportCsv"
            class="flex items-center gap-2 px-4 py-2 bg-emerald-700/80 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl transition border border-emerald-600/50">
            <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <svg wire:loading wire:target="exportCsv" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Export CSV
        </button>
        @endif
    </div>
    @endif

    {{-- ══ Filters ══════════════════════════════════════════════════════════ --}}
    <div class="bg-slate-900/70 border border-slate-800/60 rounded-2xl p-4">

        @if($isMfrStaff)
        {{-- Manufacturer: centered date range only, no apply/reset --}}
        <div class="flex items-end justify-center gap-4">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">From</label>
                <input wire:model.live="dateFrom" type="date"
                    class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:border-indigo-500 w-44 text-center">
            </div>
            <div class="pb-2.5 text-slate-500 font-bold text-sm select-none">→</div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">To</label>
                <input wire:model.live="dateTo" type="date"
                    class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:border-indigo-500 w-44 text-center">
            </div>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Date from --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">From</label>
                <input wire:model.live="dateFrom" type="date"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
            </div>
            {{-- Date to --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">To</label>
                <input wire:model.live="dateTo" type="date"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
            </div>

            @if(!$isMfrStaff)
            {{-- Business --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Business</label>
                <select wire:model.live="businessId"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                    <option value="">All</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Branch --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Branch</label>
                <select wire:model.live="branchId"
                    @if($branches->isEmpty()) disabled @endif
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500 disabled:opacity-40">
                    <option value="">{{ $businessId ? 'All Branches' : '— Select Business —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Category --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Category</label>
                <select wire:model.live="categoryId"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                    <option value="">All</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Apply / Reset --}}
            <div class="flex items-end gap-2">
                <button wire:click="$refresh"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded-lg transition">
                    Apply
                </button>
                <button wire:click="$set('dateFrom', '{{ now()->format('Y-m-d') }}'); $set('dateTo', '{{ now()->format('Y-m-d') }}'); $set('businessId', ''); $set('branchId', ''); $set('categoryId', ''); $set('productId', '');"
                    class="flex-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-bold py-2 rounded-lg transition">
                    Reset
                </button>
            </div>
        </div>
        @endif

        {{-- Item dropdown — only shown on Sales / Purchasing tabs --}}
        @if(!$isMfrStaff && in_array($activeTab, ['sales', 'purchasing']))
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Item</label>
                <select wire:model.live="productId"
                    @if($products->isEmpty()) disabled @endif
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500 disabled:opacity-40">
                    <option value="">{{ $categoryId ? 'All Items' : '— Select Category First —' }}</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif
    </div>

    {{-- ══ Tabs ══════════════════════════════════════════════════════════════ --}}
    @php
        $isManufacturingStaff = auth()->user()->can('manufacturing.produce')
            && !auth()->user()->can('business.update')
            && !auth()->user()->isSuperAdmin();
        
        $isSeller = auth()->user()->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();

        $allTabs = [
            'stock_changes'          => ['label' => '📊 Stock Changes',     'mfrOnly' => false, 'sellerHide' => true,  'gmOnly' => false, 'gmHide' => false],
            'raw_material_deduction' => ['label' => '📉 Raw Material',      'mfrOnly' => false, 'sellerHide' => true,  'gmOnly' => false, 'gmHide' => false],
            'sales'                  => ['label' => '💰 Sales',             'mfrOnly' => false, 'sellerHide' => false, 'gmOnly' => false, 'gmHide' => false],
            'profit'                 => ['label' => '📈 Profit',            'mfrOnly' => false, 'sellerHide' => true,  'gmOnly' => false, 'gmHide' => true],
            'manufacturing'          => ['label' => '🏭 Manufacturing',     'mfrOnly' => false, 'sellerHide' => true,  'gmOnly' => false, 'gmHide' => false],
            'gm_manufacturing'       => ['label' => '🏭 Manufactured Products','mfrOnly' => false,'sellerHide' => true, 'gmOnly' => true,  'gmHide' => false],
            'purchasing'             => ['label' => '🛒 Purchasing',        'mfrOnly' => false, 'sellerHide' => true,  'gmOnly' => false, 'gmHide' => false],
            'transfers'              => ['label' => '⟷ Transfers',          'mfrOnly' => false, 'sellerHide' => false, 'gmOnly' => false, 'gmHide' => false],
            'manufacturer_report'    => ['label' => '🔬 Mfr Report',        'mfrOnly' => true,  'sellerHide' => false, 'gmOnly' => false, 'gmHide' => false],
            'mfr_sales'              => ['label' => '💰 Sales Report',      'mfrOnly' => true,  'sellerHide' => false, 'gmOnly' => false, 'gmHide' => false],
        ];

        $isGeneralManager = auth()->user()->can('business.update')
            && !auth()->user()->isSuperAdmin();

        $visibleTabs = collect($allTabs)->filter(function ($meta, $key) use ($isManufacturingStaff, $isSeller, $isGeneralManager) {
            if ($isManufacturingStaff) {
                return $meta['mfrOnly'] === true;
            }
            if ($isSeller) {
                return $meta['sellerHide'] === false && $meta['mfrOnly'] === false;
            }
            if ($isGeneralManager) {
                if ($meta['mfrOnly']) return false;
                if ($meta['gmHide'])  return false;
                if ($key === 'manufacturing') return false; // replaced by gm_manufacturing for GM
                return true;
            }
            // Super Admin / Admin: all non-mfrOnly tabs
            return $meta['mfrOnly'] === false && $meta['gmOnly'] === false;
        });
    @endphp
    <div class="flex gap-1 flex-wrap border-b border-slate-800 -mb-3">
        @foreach($visibleTabs as $tab => $meta)
        <button wire:click="setTab('{{ $tab }}')"
            class="px-3 py-2.5 text-xs font-bold transition border-b-2 whitespace-nowrap
                   {{ $activeTab === $tab
                       ? 'border-indigo-500 text-indigo-400'
                       : 'border-transparent text-slate-500 hover:text-slate-300' }}">
            {{ $meta['label'] }}
        </button>
        @endforeach
    </div>

    {{-- loading overlay --}}
    <div wire:loading wire:target="setTab,businessId,branchId,categoryId,productId,mfrReportType,mfrCategoryId,mfrItemId,dateFrom,dateTo,$refresh"
         class="fixed inset-0 bg-slate-950/50 z-50 flex items-center justify-center pointer-events-none">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl px-6 py-4 flex items-center gap-3 shadow-2xl">
            <svg class="w-5 h-5 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-slate-200 text-sm font-semibold">Loading report...</span>
        </div>
    </div>

    {{-- ══ TAB CONTENT ══════════════════════════════════════════════════════ --}}

    @if($activeTab === 'executive')
        @include('livewire.admin.reports.partials.executive', ['kpis' => $kpis, 'reportData' => $reportData])
    @elseif($activeTab === 'stock_changes')
        @include('livewire.admin.reports.partials.stock-changes', ['reportData' => $reportData])
    @elseif($activeTab === 'raw_material_deduction')
        @include('livewire.admin.reports.partials.raw-material-deduction', ['reportData' => $reportData])
    @elseif($activeTab === 'sales')
        @include('livewire.admin.reports.partials.sales', ['reportData' => $reportData])
    @elseif($activeTab === 'profit')
        @include('livewire.admin.reports.partials.profit', ['reportData' => $reportData])
    @elseif($activeTab === 'inventory')
        @include('livewire.admin.reports.partials.inventory', ['reportData' => $reportData])
    @elseif($activeTab === 'manufacturing')
        @include('livewire.admin.reports.partials.manufacturing', ['reportData' => $reportData])
    @elseif($activeTab === 'gm_manufacturing')
        @include('livewire.admin.reports.partials.gm-manufacturing', ['reportData' => $reportData])
    @elseif($activeTab === 'purchasing')
        @include('livewire.admin.reports.partials.purchasing', ['reportData' => $reportData])
    @elseif($activeTab === 'branches')
        @include('livewire.admin.reports.partials.branches', ['reportData' => $reportData])
    @elseif($activeTab === 'transfers')
        @include('livewire.admin.reports.partials.transfers', ['reportData' => $reportData])
    @elseif($activeTab === 'manufacturer_report')
        @include('livewire.admin.reports.partials.manufacturer-report', [
            'reportData'     => $reportData,
            'mfrReportType'  => $mfrReportType,
            'mfrCategories'  => $mfrCategories,
            'mfrItems'       => $mfrItems,
            'mfrCategoryId'  => $mfrCategoryId,
            'mfrItemId'      => $mfrItemId,
        ])
    @elseif($activeTab === 'mfr_sales')
        @include('livewire.admin.reports.partials.mfr-sales', ['reportData' => $reportData])
    @endif

</div>
