<div class="flex gap-6 items-start">
<div class="space-y-6">

    {{-- ══ ADD PURCHASE FORM ══ --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="flex items-center gap-2 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ══ ADD PURCHASE FORM ══ --}}
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-5">
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Record New Purchase</p>

        {{-- Row 1: Business · Branch · Type · Category --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $this->isBookstoreBusiness() ? '3' : '4' }} gap-4">

            {{-- Business --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Business <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="business_id"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition">
                    <option value="">— Select Business —</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b['id'] }}" @selected($business_id == $b['id'])>{{ $b['name'] }}</option>
                    @endforeach
                </select>
                @error('business_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Branch --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Branch <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="branch_id"
                    @if(empty($branches)) disabled @endif
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value="">{{ !$business_id ? '— Select business first —' : '— Select Branch —' }}</option>
                    @foreach($branches as $br)
                        <option value="{{ $br['id'] }}" @selected($branch_id == $br['id'])>{{ $br['name'] }}</option>
                    @endforeach
                </select>
                @error('branch_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Type (hidden for bookstore business) --}}
            @if(!$this->isBookstoreBusiness())
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Type <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="type"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                    <option value="product">Product</option>
                    <option value="purchase">Purchase</option>
                </select>
                @error('type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @else
            {{-- Hidden type input for bookstore (always "purchase") --}}
            <input type="hidden" wire:model="type" value="purchase">
            @endif

            {{-- Category --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Category <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="category_id"
                    @if(empty($categories)) disabled @endif
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value="">{{ !$business_id ? '— Select business first —' : '— Select Category —' }}</option>
                    @foreach($categories as $c)
                        <option value="{{ $c['id'] }}" @selected($category_id == $c['id'])>{{ $c['name'] }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Row 1b: Item (full width) --}}
        <div class="grid grid-cols-1 gap-4">
            {{-- Item --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Item <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="product_id"
                    @if(empty($products)) disabled @endif
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value="">{{ !$category_id ? '— Select category first —' : (empty($products) ? '— No items found —' : '— Select Item —') }}</option>
                    @foreach($products as $p)
                        <option value="{{ $p['id'] }}" @selected($product_id == $p['id'])>{{ $p['name'] }}</option>
                    @endforeach
                </select>
                @error('product_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Row 1c: Total Manufactured Qty (only for product type) --}}
        @if($type === 'product' && $product_id)
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Total Manufactured Qty
                    <span class="text-xs text-slate-500">(Added by Manufacturer)</span>
                </label>
                <div class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-emerald-400 border-emerald-500/30">
                    {{ number_format($this->getManufacturedQuantity(), 3) }} units
                </div>
                <p class="text-xs text-slate-500 mt-1">This shows the total quantity manufactured for the selected item.</p>
            </div>
        </div>
        @endif

        {{-- Row 2: Qty · Unit · Purchase Price · Selling Price --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-slate-800">
            @if(!$this->shouldHideQuantity())
            {{-- Quantity --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Quantity <span class="text-red-400">*</span>
                </label>
                <input 
                    wire:model.blur="quantity" 
                    type="number" 
                    step="0.001" 
                    min="0" 
                    placeholder="Enter quantity" 
                    autocomplete="off"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                
                @error('quantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Unit --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Unit <span class="text-red-400">*</span>
                </label>
                <select wire:model="unit"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 outline-none transition">
                    <option value="">— Select Unit —</option>
                    @foreach($unitOptions as $u)
                        <option value="{{ $u }}" @selected($unit === $u)>{{ ucfirst($u) }}</option>
                    @endforeach
                </select>
                @error('unit') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- Purchase Price --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Purchase Price (ETB) <span class="text-red-400">*</span>
                </label>
                <input wire:model="unit_price" type="number" step="0.01" min="0" placeholder="0.00" autocomplete="off"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                @error('unit_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Selling Price --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">
                    Selling Price (ETB)
                    @if($type === 'purchase' && !$this->isBookstoreBusiness())
                        <span class="text-slate-500 font-normal">(Optional)</span>
                    @else
                        <span class="text-red-400">*</span>
                    @endif
                    @if((float)$unit_price > 0 && $selling_price !== '')
                        <span class="text-slate-600 font-normal">≥ {{ number_format((float)$unit_price, 2) }}</span>
                    @endif
                </label>
                <input wire:model="selling_price" type="number" step="0.01" min="0" autocomplete="off"
                    placeholder="{{ ($type === 'purchase' && !$this->isBookstoreBusiness()) ? 'Optional' : '0.00' }}"
                    class="w-full bg-slate-950 border {{ $sellingPriceError ? 'border-red-500' : 'border-slate-700' }} rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                @if($sellingPriceError)
                    <p class="text-red-400 text-xs mt-1">{{ $sellingPriceError }}</p>
                @endif
                @error('selling_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end pt-4 border-t border-slate-800">
            <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 transition flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Purchase
                </span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </div>

    {{-- ══ FILTER BAR ══ --}}
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-4 shadow-xl">
        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-3">Products List</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            {{-- Business --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Business</label>
                <select wire:model.live="filterBusinessId"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition">
                    <option value="">— All Businesses —</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Branch --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Branch</label>
                <select wire:model.live="filterBranchId"
                    @if(empty($filterBranches)) disabled @endif
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value="">{{ !$filterBusinessId ? '— Select business first —' : '— All Branches —' }}</option>
                    @foreach($filterBranches as $br)
                        <option value="{{ $br['id'] }}">{{ $br['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Category</label>
                <select wire:model.live="filterCategoryId"
                    @if(empty($filterCategories)) disabled @endif
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value="">{{ !$filterBusinessId ? '— Select business first —' : '— All Categories —' }}</option>
                    @foreach($filterCategories as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Item --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Item</label>
                <select wire:model.live="filterProductId"
                    @if(empty($filterProducts)) disabled @endif
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                    <option value="">{{ !$filterCategoryId ? '— Select category first —' : '— All Items —' }}</option>
                    @foreach($filterProducts as $p)
                        <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                    @endforeach
                </select>
            </div>

        </div>
        @if($filterBusinessId || $filterBranchId || $filterCategoryId || $filterProductId)
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="clearFilters"
                    class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold transition flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- ══ PRODUCTS TABLE ══ --}}
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl shadow-xl overflow-hidden">
        @if($purchases->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-800/20 text-[11px] uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3 text-left font-bold">Branch</th>
                            <th class="px-5 py-3 text-left font-bold">Category</th>
                            <th class="px-5 py-3 text-left font-bold">Item Name</th>
                            <th class="px-5 py-3 text-right font-bold">Total Qty</th>
                            <th class="px-5 py-3 text-right font-bold">Unit Price</th>
                            <th class="px-5 py-3 text-right font-bold">Selling Price</th>
                            <th class="px-5 py-3 text-right font-bold">Total Price</th>
                            <th class="px-5 py-3 text-left font-bold">Date</th>
                            <th class="px-5 py-3 text-center font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @foreach($purchases as $purchase)
                            @php
                                // Use total_purchase_qty if available (grouped), otherwise use single purchase quantity
                                $purchaseQty = (float) ($purchase->total_purchase_qty ?? $purchase->quantity);
                                $unitPrice = (float) $purchase->unit_price;
                                $purchaseCount = (int) ($purchase->purchase_count ?? 1);
                                
                                // For product type, add manufactured quantity
                                if ($purchase->purchase_type === 'product') {
                                    $product = $purchase->product;
                                    $manufacturingQty = 0;
                                    if ($product && $product->metadata) {
                                        $manufacturingQty = (float) ($product->metadata['manufacturing_quantity'] ?? 0);
                                    }
                                    $totalQty = $purchaseQty + $manufacturingQty;
                                } else {
                                    $totalQty = $purchaseQty;
                                }
                                
                                $totalPrice = $purchaseQty * $unitPrice;
                            @endphp
                            <tr wire:key="pur-{{ $purchase->id }}" class="hover:bg-slate-800/40 transition">
                                <td class="px-5 py-3 text-slate-400 text-xs">{{ $purchase->branch->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-400 text-xs">{{ $purchase->category->name ?? 'Unknown' }}</td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-white text-sm">{{ $purchase->product->name ?? '—' }}</p>
                                    {{-- Show count if product was purchased multiple times --}}
                                    @if($purchaseCount > 1)
                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="px-1.5 py-0.5 bg-orange-500/20 border border-orange-500/30 rounded text-orange-400 font-semibold">{{ $purchaseCount }}x</span>
                                            purchased
                                        </span>
                                    </p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-white text-xs">
                                    @if($purchase->purchase_type === 'product' && $manufacturingQty > 0)
                                        {{-- Oil product: show total purchased qty --}}
                                        <div class="flex flex-col items-end gap-0.5">
                                            <span class="text-emerald-400 font-bold">{{ number_format($purchaseQty, 0) }}</span>
                                            <span class="text-[10px] text-slate-500">purchased</span>
                                        </div>
                                    @else
                                        {{-- Regular product: show total purchased qty --}}
                                        <div class="flex flex-col items-end gap-0.5">
                                            <span class="text-indigo-300 font-semibold">{{ rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') }}</span>
                                            <span class="text-[10px] text-slate-500">{{ $purchase->unit }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-white text-xs">{{ number_format($unitPrice, 2) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs">
                                    @php $sp = (float)($purchase->product->selling_price ?? 0); @endphp
                                    @if($sp > 0)
                                        <span class="text-indigo-300 font-bold">{{ number_format($sp, 2) }}</span>
                                    @else
                                        <span class="text-slate-500 italic text-[10px]">Not set</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-emerald-400 text-xs font-bold">{{ number_format($totalPrice, 2) }}</td>
                                <td class="px-5 py-3 text-slate-400 text-xs">{{ $purchase->purchase_date->format('Y-m-d') }}</td>
                                <td class="px-5 py-3 text-center">
                                    @php
                                        // For oil products (purchase_type = 'product' with mfg qty), disable edit/delete
                                        $isOilProduct = $purchase->purchase_type === 'product' && $manufacturingQty > 0;
                                    @endphp
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Edit Button: Disabled for oil categories --}}
                                        <button type="button" 
                                            wire:click="openEdit({{ $purchase->id }})"
                                            wire:loading.attr="disabled"
                                            @disabled($isOilProduct)
                                            class="p-1.5 rounded-lg bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600/20" 
                                            title="{{ $isOilProduct ? 'Cannot edit manufactured oil products' : 'Edit Purchase' }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        
                                        {{-- Delete Button: Disabled for oil categories --}}
                                        <button type="button" 
                                            wire:click="confirmDelete({{ $purchase->id }})"
                                            wire:loading.attr="disabled"
                                            @disabled($isOilProduct)
                                            class="p-1.5 rounded-lg bg-red-600/20 hover:bg-red-600/40 text-red-400 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-red-600/20" 
                                            title="{{ $isOilProduct ? 'Cannot delete manufactured oil products' : 'Delete Purchase' }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-800 bg-slate-950/30">
                {{ $purchases->links() }}
            </div>
        @else
            <div class="py-16 text-center text-slate-500 text-sm">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <p class="font-semibold text-slate-400">No products recorded yet.</p>
                <p class="text-xs text-slate-600 mt-1">Use the form above to record products.</p>
            </div>
        @endif
    </div>

    {{-- ══ EDIT MODAL ══ --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeEdit">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/60 sticky top-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-white">Edit Purchase</h3>
                    </div>
                    <button type="button" wire:click="closeEdit" class="text-slate-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="updatePurchase" class="p-6 space-y-5">

                    {{-- Row 1: Business · Branch · Category · Item --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Business <span class="text-red-400">*</span></label>
                            <select wire:model.live="editBusinessId"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                                <option value="">— Select —</option>
                                @foreach($businesses as $b)
                                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                                @endforeach
                            </select>
                            @error('editBusinessId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Branch <span class="text-red-400">*</span></label>
                            <select wire:model.live="editBranchId"
                                @if(empty($editBranches)) disabled @endif
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                                <option value="">— Select —</option>
                                @foreach($editBranches as $br)
                                    <option value="{{ $br['id'] }}">{{ $br['name'] }}</option>
                                @endforeach
                            </select>
                            @error('editBranchId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Category <span class="text-red-400">*</span></label>
                            <select wire:model.live="editCategoryId"
                                @if(empty($editCategories)) disabled @endif
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                                <option value="">— Select —</option>
                                @foreach($editCategories as $c)
                                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                @endforeach
                            </select>
                            @error('editCategoryId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Item <span class="text-red-400">*</span></label>
                            <select wire:model.live="editProductId"
                                @if(empty($editProducts)) disabled @endif
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition disabled:opacity-40">
                                <option value="">— Select —</option>
                                @foreach($editProducts as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                                @endforeach
                            </select>
                            @error('editProductId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Row 2: Qty · Unit · Purchase Price · Selling Price --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Quantity <span class="text-red-400">*</span></label>
                            <input wire:model="editQuantity" type="number" step="0.001" min="0.001" autocomplete="off"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                            @error('editQuantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Unit <span class="text-red-400">*</span></label>
                            <select wire:model="editUnit"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 outline-none transition">
                                <option value="">— Select —</option>
                                @foreach($unitOptions as $u)
                                    <option value="{{ $u }}" @selected($editUnit === $u)>{{ ucfirst($u) }}</option>
                                @endforeach
                            </select>
                            @error('editUnit') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Purchase Price (ETB) <span class="text-red-400">*</span></label>
                            <input wire:model="editUnitPrice" type="number" step="0.01" min="0" autocomplete="off"
                                class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                            @error('editUnitPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Selling Price (ETB)
                                @php
                                    $editIsOil = $this->businessIdIsOil($editBusinessId);
                                    $editIsBookstore = $editBusinessId ? collect($this->businesses)->firstWhere('id', (int)$editBusinessId) && str_contains(strtolower(collect($this->businesses)->firstWhere('id', (int)$editBusinessId)['name'] ?? ''), 'book') || str_contains(strtolower(collect($this->businesses)->firstWhere('id', (int)$editBusinessId)['name'] ?? ''), 'albereka') : false;
                                @endphp
                                @if($editIsOil && !$editIsBookstore)
                                    <span class="text-slate-500 font-normal">(Optional)</span>
                                @else
                                    <span class="text-red-400">*</span>
                                @endif
                                @if((float)$editUnitPrice > 0 && $editSellingPrice !== '')
                                    <span class="text-slate-600 font-normal">≥ {{ number_format((float)$editUnitPrice, 2) }}</span>
                                @endif
                            </label>
                            <input wire:model="editSellingPrice" type="number" step="0.01" min="0" autocomplete="off"
                                placeholder="{{ ($editIsOil && !$editIsBookstore) ? 'Optional' : '0.00' }}"
                                class="w-full bg-slate-950 border {{ $editSellingPriceError ? 'border-red-500' : 'border-slate-700/80' }} rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:border-indigo-500 outline-none transition">
                            @if($editSellingPriceError)
                                <p class="text-red-400 text-xs mt-1">{{ $editSellingPriceError }}</p>
                            @endif
                            @error('editSellingPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                        <button type="button" wire:click="closeEdit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition disabled:opacity-50 flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="updatePurchase">Save Changes</span>
                            <span wire:loading wire:target="updatePurchase">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ══ DELETE CONFIRM MODAL ══ --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center gap-2.5 px-6 py-4 border-b border-slate-800 bg-slate-950/60">
                    <div class="w-8 h-8 rounded-lg bg-red-600/20 text-red-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Delete Purchase</h3>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-300">Are you sure you want to delete the purchase of <span class="font-bold text-white">"{{ $deleteName }}"</span>?</p>
                    <p class="text-xs text-slate-500">This cannot be undone. Stock will not be automatically reversed.</p>
                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
                        <button type="button" wire:click="closeDelete"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Cancel</button>
                        <button type="button" wire:click="deletePurchase" wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-500 text-white transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="deletePurchase">Yes, Delete</span>
                            <span wire:loading wire:target="deletePurchase">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
