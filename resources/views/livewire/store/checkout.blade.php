<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <a href="{{ route('store.cart') }}" class="hover:text-indigo-600 transition">Cart</a>
        <span>/</span>
        <span class="text-gray-600 font-medium">Checkout</span>
    </nav>

    @if(!auth('customer')->check())
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <span class="text-5xl mb-4">🔒</span>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Sign in to continue</h2>
        <p class="text-gray-500 text-sm mb-6">You need to be signed in to place an order.</p>
        <a href="{{ route('customer.login') }}"
           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm">
            Sign In
        </a>
    </div>
    @elseif($summary && empty($summary['items']))
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <span class="text-5xl mb-4">🛒</span>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Your cart is empty</h2>
        <a href="{{ route('store.products') }}"
           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm mt-4">
            Shop Now
        </a>
    </div>
    @else

    {{-- Progress steps --}}
    <div class="flex items-center gap-2 mb-8 text-sm">
        <span class="flex items-center gap-1.5 text-emerald-600 font-semibold">
            <span class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-xs font-bold">✓</span>
            Cart
        </span>
        <span class="flex-1 h-px bg-gray-200"></span>
        <span class="flex items-center gap-1.5 text-indigo-600 font-semibold">
            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">2</span>
            Checkout
        </span>
        <span class="flex-1 h-px bg-gray-200"></span>
        <span class="flex items-center gap-1.5 text-gray-400">
            <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold">3</span>
            Confirmation
        </span>
    </div>

    {{-- Error banner --}}
    @if($errorMessage)
    <div class="mb-6 flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $errorMessage }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

        {{-- ── LEFT: Delivery + Payment ──────────────────────────────── --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Delivery address --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="font-bold text-gray-900 text-base mb-5 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">1</span>
                    Delivery Address
                </h2>

                {{-- Saved addresses --}}
                @if($addresses->isNotEmpty())
                <div class="space-y-2 mb-4">
                    @foreach($addresses as $addr)
                    <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition
                        {{ $selectedAddressId == $addr->id && !$useNewAddress ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio"
                            wire:model.live="selectedAddressId"
                            value="{{ $addr->id }}"
                            wire:click="$set('useNewAddress', false)"
                            class="mt-1 accent-indigo-600">
                        <div class="text-sm">
                            <p class="font-semibold text-gray-900">{{ $addr->city }}{{ $addr->sub_city ? ', '.$addr->sub_city : '' }}</p>
                            <p class="text-gray-500">{{ $addr->street }}</p>
                            @if($addr->is_default)
                            <span class="inline-block mt-1 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg">Default</span>
                            @endif
                        </div>
                    </label>
                    @endforeach

                    {{-- Use new address --}}
                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition
                        {{ $useNewAddress ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio"
                            wire:model.live="useNewAddress"
                            value="1"
                            wire:click="$set('selectedAddressId', null); $set('useNewAddress', true)"
                            class="accent-indigo-600">
                        <span class="text-sm font-medium text-gray-700">+ Use a different address</span>
                    </label>
                </div>
                @endif

                {{-- New address form --}}
                @if($useNewAddress || $addresses->isEmpty())
                <div class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">City <span class="text-red-500">*</span></label>
                            <input wire:model="city" type="text"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:border-indigo-500 outline-none transition
                                       @error('city') border-red-400 @else border-gray-300 @enderror"
                                placeholder="Addis Ababa">
                            @error('city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Sub-city / District</label>
                            <input wire:model="subCity" type="text"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 outline-none transition"
                                placeholder="Bole">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Street / Building <span class="text-red-500">*</span></label>
                        <input wire:model="street" type="text"
                            class="w-full px-3 py-2.5 border rounded-xl text-sm focus:border-indigo-500 outline-none transition
                                   @error('street') border-red-400 @else border-gray-300 @enderror"
                            placeholder="Street name, building number">
                        @error('street') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if($addresses->isNotEmpty())
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input wire:model="saveAddress" type="checkbox" class="w-4 h-4 rounded accent-indigo-600">
                        Save this address for future orders
                    </label>
                    @endif
                </div>
                @endif
            </div>

            {{-- Contact info --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="font-bold text-gray-900 text-base mb-5 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">2</span>
                    Contact Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Full Name</label>
                        <input wire:model="fullName" type="text"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm bg-gray-50 text-gray-600 outline-none"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone</label>
                        <input wire:model="phone" type="tel"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 outline-none transition"
                            placeholder="+251 9 ...">
                    </div>
                </div>
            </div>

            {{-- Order notes --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h2 class="font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">3</span>
                    Order Notes <span class="text-gray-400 font-normal text-sm">(optional)</span>
                </h2>
                <textarea wire:model="notes" rows="3"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:border-indigo-500 outline-none transition resize-none"
                    placeholder="Any special instructions for your order..."></textarea>
            </div>
        </div>

        {{-- ── RIGHT: Order Summary ───────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-24 space-y-5">
                <h2 class="font-bold text-gray-900 text-base">Order Summary</h2>

                {{-- Items --}}
                <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                    @foreach($summary['items'] as $item)
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 shrink-0 bg-gray-100 rounded-lg flex items-center justify-center text-base">
                            📦
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 leading-snug truncate">{{ $item['product_name'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Qty: {{ number_format($item['quantity'], 0) }}</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 shrink-0">{{ number_format($item['line_total'], 2) }}</p>
                    </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal ({{ $summary['item_count'] }} items)</span>
                        <span class="font-medium text-gray-900">{{ number_format($summary['subtotal'], 2) }}</span>
                    </div>

                    @if($summary['order_discount'] > 0)
                    <div class="flex justify-between text-sm text-emerald-600">
                        <span>Discount</span>
                        <span class="font-semibold">−{{ number_format($summary['order_discount'], 2) }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Delivery fee</span>
                        <span class="text-gray-400 italic">Set by staff</span>
                    </div>

                    <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-900">
                        <span>Total</span>
                        <span class="text-lg text-indigo-700">{{ number_format($summary['total'], 2) }}</span>
                    </div>
                </div>

                {{-- Discount code --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-2">Discount Code</label>
                    <div class="flex gap-2">
                        <input wire:model="discountCode" type="text" placeholder="Enter code"
                            class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                        <button wire:click="$refresh"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition">
                            Apply
                        </button>
                    </div>
                </div>

                {{-- Payment notice --}}
                <div class="flex items-start gap-2.5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>Payment is collected manually by our staff after your order is confirmed.</span>
                </div>

                {{-- Place order --}}
                <button wire:click="placeOrder"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl transition text-sm shadow flex items-center justify-center gap-2">
                    <svg wire:loading.remove class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Place Order
                </button>

                <div class="flex items-center justify-center gap-4 text-xs text-gray-400">
                    <span>🔒 Secure</span>
                    <span>✅ Trusted</span>
                </div>
            </div>
        </div>

    </div>
    @endif

</div>
