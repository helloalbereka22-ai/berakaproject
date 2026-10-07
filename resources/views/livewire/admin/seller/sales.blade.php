<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-lg shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-white">Sell Products</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Direct branch sale — stock is deducted immediately on confirm.</p>
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

    @if(!$branchId)
        <div class="p-6 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm text-center">
            No branch is assigned to your account. Please contact an administrator.
        </div>
    @else

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- ══ LEFT: Product Picker ══ --}}
        <div class="xl:col-span-2 space-y-4">

            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">

                {{-- Row 2: Product dropdown + Available qty + Sell qty + Add --}}
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">

                    {{-- Search Item Name with Results Dropdown --}}
                    <div class="sm:col-span-5 relative">
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Search Item Name</label>
                        <input type="text" wire:model.live="searchItem"
                            placeholder="Type product name..."
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                        
                        {{-- Dropdown Results --}}
                        @if($this->searchItem && count($this->stockProducts) > 0)
                            <div class="absolute z-20 mt-1 w-full bg-slate-950 border border-slate-700 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                @foreach($this->stockProducts as $product)
                                    <button type="button" wire:click="selectProduct({{ $product->id }})"
                                        class="w-full text-left px-3 py-2.5 hover:bg-slate-800 border-b border-slate-800/50 last:border-b-0 transition flex justify-between items-center group">
                                        <div>
                                            <p class="text-sm font-semibold text-white group-hover:text-emerald-300">{{ $product->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $product->category_name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-mono text-emerald-400 font-bold">{{ number_format($product->qty_on_hand, 2) }} {{ $product->unit }}</p>
                                            <p class="text-xs text-slate-500">{{ number_format($product->selling_price, 2) }} ETB</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @elseif($this->searchItem && count($this->stockProducts) == 0)
                            <div class="absolute z-20 mt-1 w-full bg-slate-950 border border-slate-700 rounded-xl shadow-xl px-3 py-4 text-center text-sm text-slate-500">
                                No products found matching "{{ $this->searchItem }}"
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Row 3: Item dropdown (when not searching) --}}
                @if(!$this->searchItem)
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">

                    {{-- Product dropdown --}}
                    <div class="sm:col-span-5">
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Item <span class="text-red-400">*</span></label>
                        <select wire:model.live="selectedProductId"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:border-emerald-500 outline-none transition">
                            <option value="">— Select Item —</option>
                            @foreach($this->stockProducts as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Available qty badge (for dropdown selection) --}}
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Available</label>
                        <div class="h-10 flex items-center px-3 rounded-xl border
                            {{ $this->selectedProduct ? 'bg-emerald-500/10 border-emerald-600/40 text-emerald-300' : 'bg-slate-800/60 border-slate-700 text-slate-500' }}
                            font-mono text-sm font-bold">
                            @if($this->selectedProduct)
                                {{ number_format($this->selectedProduct->qty_on_hand, 2) }}
                                <span class="ml-1 text-[10px] font-normal text-emerald-600">{{ $this->selectedProduct->unit }}</span>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    {{-- Sell qty (for dropdown selection) --}}
                    <div class="sm:col-span-3">
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Sell Qty <span class="text-red-400">*</span></label>
                        <input wire:model="sellQty" type="number" step="0.001" min="0.001"
                            @if($this->selectedProduct) max="{{ $this->selectedProduct->qty_on_hand }}" @endif
                            placeholder="0"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-sm font-mono text-white focus:border-emerald-500 outline-none transition">
                    </div>

                    {{-- Add button (for dropdown selection) --}}
                    <div class="sm:col-span-2">
                        <button type="button" wire:click="addItem"
                            wire:loading.attr="disabled" wire:target="addItem"
                            class="w-full h-10 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer shadow-lg shadow-emerald-600/20">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add
                        </button>
                    </div>

                </div>
                @endif

                {{-- Unit price hint --}}
                @if($this->selectedProduct)
                    <p class="text-xs text-slate-500">
                        Unit price:
                        <span class="font-mono font-semibold text-indigo-300">{{ number_format($this->selectedProduct->selling_price, 2) }} ETB</span>
                        &middot; Category: <span class="text-slate-400">{{ $this->selectedProduct->category_name }}</span>
                    </p>
                @endif
            </div>

            {{-- ══ Items Table ══ --}}
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Sale Items</p>
                    @if(count($cartItems) > 0)
                        <span class="text-[10px] font-bold bg-emerald-500/15 text-emerald-400 px-2 py-0.5 rounded-full">
                            {{ count($cartItems) }} item(s)
                        </span>
                    @endif
                </div>

                @if(empty($cartItems))
                    <div class="py-12 text-center text-slate-600 text-sm">
                        <svg class="w-8 h-8 mx-auto mb-2 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        No items added yet. Use the picker above.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-950/50 text-[11px] uppercase tracking-widest text-slate-400 border-b border-slate-800">
                                    <th class="text-left px-5 py-3 font-bold">Item Name</th>
                                    <th class="text-right px-5 py-3 font-bold">Qty</th>
                                    <th class="text-right px-5 py-3 font-bold">Unit Price</th>
                                    <th class="text-right px-5 py-3 font-bold">Total Price</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                @foreach($cartItems as $i => $item)
                                    <tr wire:key="sale-item-{{ $i }}" class="hover:bg-slate-800/30 transition">
                                        <td class="px-5 py-3">
                                            <p class="font-semibold text-white">{{ $item['name'] }}</p>
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">
                                            {{ number_format($item['qty'], 3) }}
                                            <span class="text-slate-500">{{ $item['unit'] }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">
                                            {{ number_format($item['unit_price'], 2) }} ETB
                                        </td>
                                        <td class="px-5 py-3 text-right font-mono font-bold text-white text-sm">
                                            {{ number_format($item['total_price'], 2) }} ETB
                                        </td>
                                        <td class="px-5 py-3 text-right">
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
                @endif
            </div>

        </div>

        {{-- ══ RIGHT: Summary + Sell ══ --}}
        <div class="space-y-4">

            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4 sticky top-20">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Sale Summary</p>

                @if(empty($cartItems))
                    <p class="text-xs text-slate-600 text-center py-4">No items in sale yet.</p>
                @else
                    {{-- Item breakdown --}}
                    <div class="space-y-2">
                        @foreach($cartItems as $item)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400 truncate pr-2">{{ $item['name'] }} × {{ number_format($item['qty'], 2) }}</span>
                                <span class="font-mono font-semibold text-white shrink-0">{{ number_format($item['total_price'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-slate-700 pt-3 flex items-center justify-between">
                        <span class="text-sm font-bold text-white">Total</span>
                        <span class="font-mono font-extrabold text-emerald-300 text-lg">{{ number_format($this->grandTotal, 2) }} ETB</span>
                    </div>

                    {{-- Sell button --}}
                    <button type="button" wire:click="confirmSale"
                        wire:loading.attr="disabled" wire:target="sell"
                        class="w-full py-3 rounded-xl text-sm font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white
                               shadow-lg shadow-emerald-600/25 transition flex items-center justify-center gap-2
                               disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="sell" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirm Sale
                        </span>
                        <span wire:loading wire:target="sell">Processing...</span>
                    </button>
                @endif

                {{-- Branch info --}}
                <div class="pt-2 border-t border-slate-800 text-[10px] text-slate-600">
                    Branch ID: {{ $branchId ?? '—' }}
                </div>
            </div>

        </div>

    </div>

    @endif {{-- end if branchId --}}

    {{-- Confirmation Modal --}}
    @if($showConfirmModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 p-6 shadow-xl">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6.172 6.172a4 4 0 015.656 0L12 7.343m0 0a4 4 0 015.656 0M12 7.343L10.343 5.5a4 4 0 00-5.656 5.656L12 19M12 7.343l1.657-1.657a4 4 0 015.656 0" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Confirm Sale?</h3>
                    <p class="text-sm text-slate-400 mt-1">Stock will be deducted immediately.</p>
                </div>
            </div>

            <div class="bg-slate-800/50 rounded-xl p-4 mb-6 text-sm text-slate-300">
                <p><strong>Total Items:</strong> {{ count($cartItems) }}</p>
                <p><strong>Total Amount:</strong> <span class="font-mono font-bold text-emerald-300">{{ number_format($this->grandTotal, 2) }} ETB</span></p>
            </div>

            <p class="text-sm text-slate-400 mb-6">This action cannot be reversed. Please verify the details above.</p>

            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showConfirmModal', false)" 
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                    Cancel
                </button>
                <button wire:click="sell" 
                    wire:loading.attr="disabled" wire:target="sell"
                    class="px-6 py-2 rounded-xl text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center gap-2 disabled:opacity-50 cursor-pointer shadow-lg shadow-emerald-600/20">
                    <span wire:loading.remove wire:target="sell">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span wire:loading.remove wire:target="sell">Confirm</span>
                    <span wire:loading wire:target="sell">Processing...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
