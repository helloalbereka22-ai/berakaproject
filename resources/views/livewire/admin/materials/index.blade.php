<div class="space-y-6">
    {{-- Header — hidden for manufacturing staff --}}
    @php $isMfrStaff = auth()->user()->can('manufacturing.produce') && !auth()->user()->can('business.update') && !auth()->user()->isSuperAdmin(); @endphp
    @unless($isMfrStaff)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Raw Materials</h2>
            <p class="text-sm text-slate-400 mt-1">Manage manufacturing raw materials and packaging materials.</p>
        </div>
        @can('product.create')
        <a href="{{ route('admin.products.create') }}" wire:navigate
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Material
        </a>
        @endcan
    </div>
    @endunless

    @if(session('success'))<div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ session('error') }}</div>@endif

    {{-- Tab Summary Badges — hidden for manufacturing staff --}}
    @unless($isMfrStaff)
    <div class="flex gap-3 flex-wrap">
        <button wire:click="switchTab('all')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition
                {{ $tab === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700' }}">
            All Materials
            <span class="ml-1.5 text-xs opacity-70">{{ $summaryTotals['raw'] + $summaryTotals['packaging'] }}</span>
        </button>
        <button wire:click="switchTab('raw')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition
                {{ $tab === 'raw' ? 'bg-sky-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700' }}">
            Raw Materials
            <span class="ml-1.5 text-xs opacity-70">{{ $summaryTotals['raw'] }}</span>
        </button>
        <button wire:click="switchTab('packaging')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition
                {{ $tab === 'packaging' ? 'bg-violet-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700' }}">
            Packaging Materials
            <span class="ml-1.5 text-xs opacity-70">{{ $summaryTotals['packaging'] }}</span>
        </button>
    </div>
    @endunless

    {{-- Filters --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, code..."
            class="col-span-2 lg:col-span-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">

        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
        </select>

        <select wire:model.live="unit" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Units</option>
            @foreach($units as $u)<option value="{{ $u }}">{{ strtoupper($u) }}</option>@endforeach
        </select>

        <select wire:model.live="status" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Statuses</option>
            @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                    @if($isMfrStaff)
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Price</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Selling Price</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Price</th>
                    @else
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Material</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Qty</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost Price</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Value</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Minimum Qty</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Business</th>
                        <th class="text-center px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4"></th>
                    @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($materials as $material)
                    @if($isMfrStaff)
                        {{-- Manufacturer staff simplified row --}}
                        @php
                            $qty        = (float) $material->current_stock;
                            $unitPrice  = (float) ($material->cost_price ?? 0);
                            $sellPrice  = (float) ($material->selling_price ?? 0);
                            $totalPrice = $qty * $unitPrice;
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-4 text-slate-300 text-sm">
                                {{ optional($material->category)->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white">{{ $material->name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ strtoupper($material->unit ?? '') }}</div>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-200">
                                {{ number_format($qty, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-sky-300">
                                {{ number_format($unitPrice, 2) }}
                                <span class="text-slate-500 text-xs"> ETB</span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-emerald-300">
                                {{ number_format($sellPrice, 2) }}
                                <span class="text-slate-500 text-xs"> ETB</span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono font-bold text-amber-300">
                                {{ number_format($totalPrice, 2) }}
                                <span class="text-slate-500 text-xs font-normal"> ETB</span>
                            </td>
                        </tr>
                    @else
                        {{-- Full row for other roles --}}
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white">{{ $material->name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $material->sku ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $typeColor = $material->type === \App\Models\Product::TYPE_RAW_MATERIAL ? 'sky' : 'violet';
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $typeColor }}-500/10 text-{{ $typeColor }}-400 border border-{{ $typeColor }}-500/20">
                                    {{ $material->getTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-300 text-sm">{{ optional($material->category)->name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex px-2 py-0.5 rounded bg-slate-700 text-slate-200 text-xs font-mono font-semibold">
                                    {{ strtoupper($material->unit ?? '—') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-200">
                                {{ number_format($material->current_stock, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-300">
                                {{ number_format($material->cost_price ?? 0, 2) }} <span class="text-slate-500 text-xs">ETB</span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono font-bold text-slate-300">
                                {{ number_format($material->current_stock * ($material->cost_price ?? 0), 2) }} <span class="text-slate-500 text-xs font-normal">ETB</span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono">
                                @if($material->reorder_point)
                                    <div class="flex flex-col items-end">
                                        <span class="text-slate-300">{{ number_format($material->reorder_point, 2) }}</span>
                                        @if($material->isLowStock())
                                            <span class="mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">LOW STOCK</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-400 text-sm">{{ optional($material->business)->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                @can('product.update')
                                <button wire:click="toggleStatus({{ $material->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold transition
                                    {{ $material->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:opacity-70' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30 hover:opacity-70' }}">
                                    {{ ucfirst($material->status) }}
                                </button>
                                @else
                                    <span class="text-xs text-slate-400">{{ ucfirst($material->status) }}</span>
                                @endcan
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @can('product.update')
                                    <a href="{{ route('admin.products.edit', $material->id) }}" wire:navigate
                                       class="p-2 rounded-lg text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10 transition"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @endcan
                                    @can('product.delete')
                                    <button wire:click="confirmDelete({{ $material->id }})"
                                        class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endif
                    @empty
                        <tr><td colspan="{{ $isMfrStaff ? 6 : 9 }}" class="px-5 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-slate-500">No materials found.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-800">{{ $materials->links() }}</div>
    </div>

    {{-- Delete Confirm Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-base font-bold text-white mb-2">Delete Material?</h3>
            <p class="text-sm text-slate-400 mb-6">This material will be soft-deleted and removed from listings.</p>
            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showDeleteConfirm',false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="delete" class="px-5 py-2 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition">Delete</button>
            </div>
        </div>
    </div>
    @endif
</div>
