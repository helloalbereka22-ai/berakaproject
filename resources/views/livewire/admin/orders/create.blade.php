<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
        <a href="{{ route('admin.branch-seller.dashboard') }}" wire:navigate
           class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-white">New Order</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Create a new sales order for your branch.</p>
        </div>
    </div>

    {{-- Error --}}
    @if($errorMessage)
        <div class="flex items-center gap-2 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Business/Branch Selector (Super Admin Only) --}}
    @if(auth()->user()->isSuperAdmin())
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="xl:col-span-2">
                <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Order Context</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">Business <span class="text-red-400">*</span></label>
                            <select wire:model.live="businessId"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition">
                                <option value="">— Select Business —</option>
                                @foreach($businesses as $bus)
                                    <option value="{{ $bus['id'] }}">{{ $bus['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">Branch <span class="text-red-400">*</span></label>
                            <select wire:model.live="branchId"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none transition {{ !$businessId ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ !$businessId ? 'disabled' : '' }}>
                                <option value="">— Select Branch —</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- LEFT: Customer + Product Picker --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Customer --}}
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Customer</p>
                    <button type="button" wire:click="toggleWalkIn"
                        class="text-xs font-semibold transition {{ $walkIn ? 'text-amber-400 hover:text-amber-300' : 'text-indigo-400 hover:text-indigo-300' }}">
                        {{ $walkIn ? '← Use registered customer' : 'Walk-in customer →' }}
                    </button>
                </div>

                @if($walkIn)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Name <span class="text-red-400">*</span></label>
                            <input wire:model="walkInName" type="text" placeholder="Customer name"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Phone (optional)</label>
                            <input wire:model="walkInPhone" type="text" placeholder="Phone number"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">
                        </div>
                    </div>
                @else
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="customerSearch" type="text"
                            placeholder="Search customer by name, phone or email..."
                            autocomplete="off"
                            class="w-full bg-slate-950 border {{ $customer_id ? 'border-emerald-600' : 'border-slate-700' }} rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">

                        {{-- Customer dropdown --}}
                        @if($showCustomerList && count($this->customerResults) > 0)
                            <div class="absolute z-30 top-full mt-1 w-full bg-slate-900 border border-slate-700 rounded-xl shadow-2xl overflow-hidden">
                                @foreach($this->customerResults as $c)
                                    <button type="button" wire:click="selectCustomer({{ $c->id }}, '{{ addslashes($c->name) }}')"
                                        class="w-full text-left px-4 py-3 hover:bg-slate-800 transition border-b border-slate-800/50 last:border-0">
                                        <p class="text-sm font-semibold text-white">{{ $c->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $c->phone ?? $c->email ?? 'No contact' }}</p>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($customer_id)
                        <p class="text-xs text-emerald-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Customer selected: <strong>{{ $customerName }}</strong>
                        </p>
                    @endif
                @endif
            </div>

            {{-- Product Picker --}}
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Add Products</p>

                {{-- Filters --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search product..."
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">
                    </div>
                    <select wire:model.live="filterCategoryId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:border-indigo-500 outline-none transition">
                        <option value="">— All Categories —</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Product list --}}
                <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
                    @forelse($this->availableProducts as $product)
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-slate-800/50 hover:bg-slate-800 transition border border-slate-700/40">
                            <div>
                                <p class="text-sm font-semibold text-white">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->category_name }} &middot; {{ number_format($product->qty_on_hand, 2) }} {{ $product->unit }} in stock</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold font-mono text-indigo-300">{{ number_format($product->selling_price, 2) }} ETB</span>
                                <button type="button" wire:click="addToCart({{ $product->id }})"
                                    class="px-3 py-1.5 rounded-lg bg-indigo-600/80 hover:bg-indigo-600 text-white text-xs font-bold transition cursor-pointer">
                                    + Add
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-600 text-xs">
                            @if(!$branchId)
                                No branch assigned to your account.
                            @else
                                No products in stock matching your search.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl">
                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-2">Order Notes (optional)</label>
                <textarea wire:model="notes" rows="2" placeholder="Any special instructions..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition resize-none"></textarea>
            </div>

        </div>

        {{-- RIGHT: Cart + Summary --}}
        <div class="space-y-5">

            {{-- Cart --}}
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-3">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                    Order Items
                    @if(count($cartItems) > 0)
                        <span class="ml-1.5 bg-indigo-500/20 text-indigo-400 px-1.5 py-0.5 rounded-md text-[10px]">{{ count($cartItems) }}</span>
                    @endif
                </p>

                @if(empty($cartItems))
                    <div class="py-8 text-center text-slate-600 text-xs">
                        No items added yet. Search and add products from the left panel.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($cartItems as $i => $item)
                            <div wire:key="cart-{{ $i }}" class="rounded-xl bg-slate-800/60 border border-slate-700/40 p-3 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-bold text-white leading-tight">{{ $item['name'] }}</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $item['category_name'] }}</p>
                                    </div>
                                    <button type="button" wire:click="removeFromCart({{ $i }})"
                                        class="text-slate-600 hover:text-red-400 transition shrink-0 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <button type="button"
                                            wire:click="$set('cartItems.{{ $i }}.qty', {{ max(0.001, ($item['qty'] ?? 1) - 1) }})"
                                            class="w-6 h-6 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold flex items-center justify-center transition cursor-pointer">−</button>
                                        <input type="number" step="0.001" min="0.001" max="{{ $item['stock'] }}"
                                            wire:model.live="cartItems.{{ $i }}.qty"
                                            class="w-16 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs font-mono text-center text-white focus:border-indigo-500 outline-none">
                                        <button type="button"
                                            wire:click="$set('cartItems.{{ $i }}.qty', {{ min($item['stock'], ($item['qty'] ?? 1) + 1) }})"
                                            class="w-6 h-6 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold flex items-center justify-center transition cursor-pointer">+</button>
                                        <span class="text-slate-600 text-[10px]">{{ $item['unit'] }}</span>
                                    </div>
                                    <span class="font-mono text-xs font-bold text-white">
                                        {{ number_format((float)$item['price'] * (float)$item['qty'], 2) }} ETB
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-600">
                                    {{ number_format($item['price'], 2) }} ETB/{{ $item['unit'] }}
                                    &middot; Stock: {{ number_format($item['stock'], 2) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Order Summary --}}
            @if(count($cartItems) > 0)
            <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-5 shadow-xl space-y-4">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Summary</p>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-slate-400">
                        <span>Subtotal</span>
                        <span class="font-mono font-semibold text-white">{{ number_format($this->subtotal, 2) }} ETB</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Discount</span>
                        <span class="font-mono text-slate-500">0.00 ETB</span>
                    </div>
                    <div class="border-t border-slate-700 pt-2 flex justify-between">
                        <span class="font-bold text-white">Total</span>
                        <span class="font-mono font-extrabold text-indigo-300 text-base">{{ number_format($this->subtotal, 2) }} ETB</span>
                    </div>
                </div>

                {{-- Payment method --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">Payment Method</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['cash' => 'Cash', 'transfer' => 'Transfer', 'credit' => 'Credit', 'other' => 'Other'] as $key => $label)
                            <button type="button" wire:click="$set('paymentMethod', '{{ $key }}')"
                                class="px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer
                                    {{ $paymentMethod === $key
                                        ? 'bg-indigo-600 border-indigo-500 text-white'
                                        : 'bg-slate-800 border-slate-700 text-slate-400 hover:border-indigo-500/50 hover:text-white' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Place Order --}}
                <button type="button" wire:click="placeOrder" wire:loading.attr="disabled"
                    class="w-full py-3 rounded-xl text-sm font-extrabold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                    <span wire:loading.remove wire:target="placeOrder" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Place Order
                    </span>
                    <span wire:loading wire:target="placeOrder">Placing order...</span>
                </button>
            </div>
            @endif

        </div>
    </div>

</div>
