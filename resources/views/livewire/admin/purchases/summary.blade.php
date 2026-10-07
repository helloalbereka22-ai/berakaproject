<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Purchase Summary</h2>
            <p class="text-sm text-slate-400 mt-1">Overview of all manual purchases by type, category and business.</p>
        </div>
        @can('inventory.view')
        <a href="{{ route('admin.purchases.create') }}" wire:navigate
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Purchase
        </a>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 p-4 bg-slate-900 border border-slate-800 rounded-2xl">
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
        </select>
        <select wire:model.live="purchaseType" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            @foreach($purchaseTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <input type="date" wire:model.live="dateFrom"
            class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <input type="date" wire:model.live="dateTo"
            class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Purchase Value</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($totalPurchased, 2) }}</p>
            <p class="text-xs text-slate-500 mt-1">ETB</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Transactions</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($purchaseCount) }}</p>
            <p class="text-xs text-slate-500 mt-1">Purchases</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Quantity</p>
            <p class="text-3xl font-extrabold text-white mt-2">{{ number_format($totalQty, 2) }}</p>
            <p class="text-xs text-slate-500 mt-1">Units (mixed)</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- By Product Type --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">By Product Type</h3>
            </div>
            <div class="p-5 space-y-3">
                @forelse($byType as $row)
                    @php
                        $typeLabels = \App\Models\Product::TYPES;
                        $typeColors = ['sale_product' => 'emerald', 'raw_material' => 'sky', 'packing_material' => 'violet', 'finished_product' => 'amber'];
                        $tc = $typeColors[$row->type] ?? 'slate';
                    @endphp
                    <div class="flex items-center justify-between py-2 border-b border-slate-800/50 last:border-0">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $tc }}-500/10 text-{{ $tc }}-400 border border-{{ $tc }}-500/20">
                            {{ $typeLabels[$row->type] ?? $row->type }}
                        </span>
                        <div class="text-right">
                            <p class="text-sm font-bold text-white">{{ number_format($row->total_value, 2) }} ETB</p>
                            <p class="text-xs text-slate-500">{{ number_format($row->count) }} purchases</p>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">No data for selected period.</p>
                @endforelse
            </div>
        </div>

        {{-- By Category --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">By Category</h3>
            </div>
            <div class="p-5 space-y-2 max-h-80 overflow-y-auto">
                @forelse($byCategory as $row)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800/50 last:border-0">
                        <div>
                            <p class="text-sm text-slate-200">{{ $row->category_name ?? 'Uncategorized' }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ number_format($row->total_qty, 2) }} {{ strtoupper($row->unit ?? '') }}</p>
                        </div>
                        <p class="text-sm font-bold text-white">{{ number_format($row->total_value, 2) }} ETB</p>
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">No data for selected period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- By Business (Super Admin only) --}}
    @if(count($byBusiness) > 0)
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white">By Business</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Business</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Purchases</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Total Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($byBusiness as $row)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3 font-medium text-slate-200">{{ $row->business_name }}</td>
                        <td class="px-5 py-3 text-right text-slate-400">{{ number_format($row->count) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-white">{{ number_format($row->total_value, 2) }} ETB</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Recent Purchases --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white">Recent Purchases</h3>
            <a href="{{ route('admin.purchases.index') }}" wire:navigate class="text-xs text-indigo-400 hover:text-indigo-300">View All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Date / Ref</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Product / Material</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Business</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Qty</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recent as $p)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3">
                            <p class="text-slate-200">{{ $p->purchase_date->format('d M Y') }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $p->reference }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-300">{{ optional($p->product)->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-400 text-xs">{{ optional($p->business)->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-300">{{ number_format($p->quantity, 2) }} {{ $p->unit }}</td>
                        <td class="px-5 py-3 text-right font-bold text-white">{{ number_format($p->total_price, 2) }} ETB</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No purchases found for the selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
