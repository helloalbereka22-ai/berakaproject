<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-orange-500 flex items-center justify-center shadow-lg shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-white">Deduct Raw Material</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Select items and quantities to deduct from raw material stock.</p>
        </div>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $successMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- ══ LEFT: Picker ══ --}}
        <div class="xl:col-span-1 space-y-4">
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Select Item</p>

                {{-- Category --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Category</label>
                    <select wire:model.live="filterCategoryId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-amber-500 outline-none transition">
                        <option value="">— All Categories —</option>
                        @foreach($categoryList as $cat)
                            <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Item --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Item <span class="text-red-400">*</span></label>
                    <select wire:model.live="selectedProductId"
                        @if(!$filterCategoryId) disabled @endif
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-amber-500 outline-none transition disabled:opacity-40">
                        <option value="">{{ $filterCategoryId ? '— Select Item —' : '— Select category first —' }}</option>
                        @foreach($stockProducts as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Available qty display --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Available Qty</label>
                    <div class="h-10 flex items-center px-3 rounded-xl border
                        {{ $selectedProductId ? 'bg-emerald-500/10 border-emerald-600/40' : 'bg-slate-800/50 border-slate-700' }}">
                        @if($selectedProductId && $availableQty > 0)
                            <span class="font-mono font-bold text-emerald-300">{{ number_format($availableQty, 3) }}</span>
                            <span class="ml-1.5 text-xs text-slate-500">{{ $selectedUnit }}</span>
                        @else
                            <span class="text-slate-600 text-sm">—</span>
                        @endif
                    </div>
                </div>

                {{-- Deduct qty --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                        Deduct Qty <span class="text-red-400">*</span>
                        @if($availableQty > 0)
                            <span class="text-slate-600 font-normal">(max {{ number_format($availableQty, 3) }})</span>
                        @endif
                    </label>
                    <input wire:model="deductQty" type="number" step="0.001" min="0.001"
                        @if($availableQty > 0) max="{{ $availableQty }}" @endif
                        placeholder="0.000"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm font-mono text-white focus:border-amber-500 outline-none transition">
                </div>

                {{-- Add button --}}
                <button type="button" wire:click="addItem"
                    wire:loading.attr="disabled" wire:target="addItem"
                    class="w-full py-2.5 rounded-xl text-sm font-bold bg-amber-600 hover:bg-amber-500 text-white transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer shadow-lg shadow-amber-600/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add to List
                </button>
            </div>
        </div>

        {{-- ══ RIGHT: Deduction list + Confirm ══ --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Table --}}
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Items to Deduct</p>
                    @if(count($cartItems) > 0)
                        <span class="text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full">
                            {{ count($cartItems) }} item(s)
                        </span>
                    @endif
                </div>

                @if(empty($cartItems))
                    <div class="py-14 text-center text-slate-600 text-sm">
                        <svg class="w-8 h-8 mx-auto mb-2 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        No items added. Use the picker to add raw materials.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-950/50 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                                    <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                                    <th class="text-right px-5 py-3.5 font-bold">Available Qty</th>
                                    <th class="text-right px-5 py-3.5 font-bold">Deduct Qty</th>
                                    <th class="px-5 py-3.5 font-bold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                @foreach($cartItems as $i => $item)
                                    <tr wire:key="deduct-{{ $i }}" class="hover:bg-slate-800/30 transition">
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-white">{{ $item['name'] }}</p>
                                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $item['category_name'] }}</p>
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono text-emerald-300 text-sm">
                                            {{ number_format($item['available_qty'], 3) }}
                                            <span class="text-slate-600 text-[10px]"> {{ $item['unit'] }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono font-bold text-amber-300 text-sm">
                                            {{ number_format($item['deduct_qty'], 3) }}
                                            <span class="text-slate-600 text-[10px]"> {{ $item['unit'] }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right">
                                            <button type="button" wire:click="removeItem({{ $i }})"
                                                class="p-1.5 rounded-lg text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Confirm button --}}
                    <div class="px-5 py-4 border-t border-slate-800 flex justify-end">
                        <button type="button" wire:click="confirmDeduction"
                            wire:loading.attr="disabled" wire:target="confirmDeduction"
                            wire:confirm="Confirm deduction? This will permanently reduce raw material stock."
                            class="px-6 py-2.5 rounded-xl text-sm font-extrabold bg-red-600 hover:bg-red-500 text-white
                                   shadow-lg shadow-red-600/25 transition flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="confirmDeduction" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Confirm Deduction
                            </span>
                            <span wire:loading wire:target="confirmDeduction">Processing...</span>
                        </button>
                    </div>
                @endif
            </div>

        </div>
    </div>

</div>
