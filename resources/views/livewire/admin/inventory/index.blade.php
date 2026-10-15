<div class="space-y-6">

    {{-- Header (hidden when viewing transfers) --}}
    @if($tab !== 'transfers')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Inventory Management</h2>
            <p class="text-sm text-slate-400 mt-1">Stock levels, movements, transfers, and adjustments across all warehouses.</p>
        </div>
    </div>
    @endif

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ session('error') }}</div>
    @endif

    {{-- KPI Cards with Date Filters --}}
    @if($tab !== 'transfers')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Items</p>
            <p class="text-3xl font-bold text-white mt-2">{{ number_format($summary['total_items']) }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Value</p>
            <p class="text-3xl font-bold text-white mt-2">{{ number_format($summary['total_value'], 2) }}</p>
        </div>
    </div>
    
    {{-- Date Filters (Movements/Transfers Tab Only) --}}
    @if($tab === 'movements' || $tab === 'transfers')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">From</label>
            <input wire:model.live.debounce.300ms="dateFrom" type="date" 
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 cursor-pointer"
                style="color-scheme: dark;">
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">To</label>
            <input wire:model.live.debounce.300ms="dateTo" type="date" 
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 cursor-pointer"
                style="color-scheme: dark;">
        </div>
        @if($dateFrom || $dateTo)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex items-end">
            <button wire:click="$set('dateFrom', ''); $set('dateTo', '')"
                class="w-full px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                Clear Dates
            </button>
        </div>
        @endif
    </div>
    @endif
    @else
        {{-- Empty div for spacing on transfers tab --}}
    @endif

    {{-- Filters (hidden on transfers tab) --}}
    @if($tab !== 'transfers')
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search product name or SKU..."
                class="col-span-1 sm:col-span-2 lg:col-span-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 outline-none">

            <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Businesses</option>
                @foreach($businesses as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </select>

            <select wire:model.live="locationId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Locations</option>
                @foreach($locations as $l)
                    <option value="{{ $l['id'] }}">{{ $l['name'] }} ({{ $l['type'] }})</option>
                @endforeach
            </select>

            <select wire:model.live="stockFilter" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Stock</option>
                <option value="low_stock">Low Stock Only</option>
                <option value="out_of_stock">Out of Stock Only</option>
            </select>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 bg-slate-900 border border-slate-800 rounded-2xl p-1.5 w-fit">
        @foreach(['levels' => 'Stock Levels', 'movements' => 'Movement History'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $tab === $key ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Stock Levels Tab --}}
    @if($tab === 'levels')
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">On Hand</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Available</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reorder</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Cost</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Value</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($stockLevels as $level)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-white">
                                    {{ optional($level->item)->name ?? 'Unknown' }}
                                </div>
                                <div class="text-xs text-slate-500">{{ optional($level->item)->sku }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-slate-300">{{ optional($level->location)->name }}</div>
                                <div class="text-xs text-slate-500">{{ optional($level->location?->branch)->name }}</div>
                            </td>
                            <td class="px-5 py-4 text-right font-mono font-bold text-white">
                                {{ number_format($level->quantity_on_hand, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-300">
                                {{ number_format($level->getAvailableQuantity(), 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-500">
                                {{ $level->reorder_level ? number_format($level->reorder_level, 2) : '—' }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-300">
                                {{ number_format($level->unit_cost, 4) }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono font-semibold text-indigo-300">
                                {{ number_format($level->getStockValue(), 2) }}
                            </td>
                            <td class="px-5 py-4">
                                @if($level->isOutOfStock())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                                        Out of Stock
                                    </span>
                                @elseif($level->isLowStock())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        OK
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @can('inventory.update')
                                <button wire:click="openAdjustModal('{{ $level->location->id }}', '{{ $level->item->id }}')"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white transition font-semibold">
                                    Adjust
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-slate-600">
                                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                No stock records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-800">
            {{ $stockLevels->links() }}
        </div>
    </div>
    @endif

    {{-- Movements Tab --}}
    @if($tab === 'movements' || $tab === 'transfers')
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                        <th class="text-right px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Balance After</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">By</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-4 text-slate-400 whitespace-nowrap">
                                {{ $movement->moved_at?->format('d M Y H:i') }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-white font-semibold">{{ optional($movement->item)->name ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-300">
                                {{ optional($movement->location)->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $movement->direction === 'in' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                                    {{ $movement->direction === 'in' ? '▲' : '▼' }} {{ $movement->getTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-mono font-bold
                                {{ $movement->direction === 'in' ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $movement->direction === 'in' ? '+' : '-' }}{{ number_format($movement->quantity, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-300">
                                {{ number_format($movement->balance_after, 2) }}
                            </td>
                            <td class="px-5 py-4 text-slate-400 text-xs">
                                {{ optional($movement->creator)->name ?? 'System' }}
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs max-w-xs truncate">
                                {{ $movement->notes }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-600">No movements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-800">
            {{ $movements->links() }}
        </div>
    </div>
    @endif

    {{-- Adjustment Modal --}}
    @if($showAdjustModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Stock Adjustment</h3>
                <button wire:click="$set('showAdjustModal', false)" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Direction</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model="adjDirection" value="increase" class="accent-emerald-500">
                            <span class="text-sm text-emerald-400 font-semibold">Increase</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model="adjDirection" value="decrease" class="accent-red-500">
                            <span class="text-sm text-red-400 font-semibold">Decrease</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Quantity <span class="text-red-400">*</span></label>
                    <input wire:model="adjQuantity" type="number" step="0.0001" min="0.0001"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                    @error('adjQuantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Reason <span class="text-red-400">*</span></label>
                    <input wire:model="adjReason" type="text" placeholder="e.g. Damaged goods, Counting correction..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                    @error('adjReason') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Notes</label>
                    <textarea wire:model="adjNotes" rows="2"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500 resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-3">
                <button wire:click="$set('showAdjustModal', false)"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="saveAdjustment"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">
                    Apply Adjustment
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Transfer Modal --}}
    @if($showTransferModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Stock Transfer</h3>
                <button wire:click="$set('showTransferModal', false)" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">From Location <span class="text-red-400">*</span></label>
                    <select wire:model="tfrFromLocationId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select source...</option>
                        @foreach($locations as $l)
                            <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                        @endforeach
                    </select>
                    @error('tfrFromLocationId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">To Location <span class="text-red-400">*</span></label>
                    <select wire:model="tfrToLocationId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select destination...</option>
                        @foreach($locations as $l)
                            <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Product <span class="text-red-400">*</span></label>
                    <input wire:model="tfrProductId" type="number" placeholder="Product ID"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Quantity <span class="text-red-400">*</span></label>
                    <input wire:model="tfrQuantity" type="number" step="0.0001" min="0.0001"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                    @error('tfrQuantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Notes</label>
                    <textarea wire:model="tfrNotes" rows="2"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500 resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-3">
                <button wire:click="$set('showTransferModal', false)"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="saveTransfer"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white transition">
                    Transfer Stock
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
