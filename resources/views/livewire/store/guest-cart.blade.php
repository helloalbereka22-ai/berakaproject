<div class="min-h-screen bg-gray-50 py-10">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('store.products') }}" class="text-gray-400 hover:text-purple-600 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-black text-gray-900">🛒 Your Cart</h1>
    </div>

    {{-- Order Placed Success --}}
    @if($orderPlaced)
    <div class="bg-white rounded-3xl shadow-xl p-10 text-center border-4 border-green-200">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="text-5xl">✅</span>
        </div>
        <h2 class="text-3xl font-black text-gray-900 mb-3">Order Placed!</h2>
        <p class="text-gray-500 text-lg mb-4">Thank you! We received your order.</p>
        <div class="inline-block bg-purple-50 border-2 border-purple-200 rounded-2xl px-8 py-4 mb-6">
            <p class="text-xs text-purple-500 font-bold uppercase tracking-wider mb-1">Order Number</p>
            <p class="text-2xl font-black text-purple-700">{{ $orderNumber }}</p>
        </div>
        <p class="text-gray-500 text-sm mb-8">We will contact you on your phone to confirm delivery.</p>
        <a href="{{ route('store.products') }}"
           class="inline-block px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-black rounded-xl hover:from-purple-700 hover:to-pink-700 transition shadow-lg">
            Continue Shopping
        </a>
    </div>

    {{-- Empty Cart --}}
    @elseif(empty($summary['items']))
    <div class="bg-white rounded-3xl shadow-xl p-10 text-center">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="text-5xl">🛒</span>
        </div>
        <h2 class="text-2xl font-black text-gray-800 mb-3">Your cart is empty</h2>
        <p class="text-gray-500 mb-8">Browse our products and add items to your cart.</p>
        <a href="{{ route('store.products') }}"
           class="inline-block px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-black rounded-xl hover:from-purple-700 hover:to-pink-700 transition shadow-lg">
            Browse Products
        </a>
    </div>

    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cart Items --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-black text-gray-900">{{ $summary['count'] }} {{ $summary['count'] === 1 ? 'Item' : 'Items' }}</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($summary['items'] as $item)
                    <div class="flex items-center gap-4 p-4">
                        {{-- Image --}}
                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                            @if($item['image'])
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-2xl text-gray-300">📦</div>
                            @endif
                        </div>

                        {{-- Name & Price --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 text-sm truncate">{{ $item['name'] }}</h3>
                            <p class="text-purple-600 font-black text-base">ETB {{ number_format($item['price'], 2) }}</p>
                        </div>

                        {{-- Qty --}}
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden shrink-0">
                            <button wire:click="updateQty({{ $item['product_id'] }}, {{ $item['quantity'] - 1 }})"
                                class="px-3 py-2 text-gray-500 hover:bg-gray-50 font-bold transition">−</button>
                            <span class="px-3 py-2 text-sm font-bold border-x border-gray-200 min-w-[2.5rem] text-center">{{ $item['quantity'] }}</span>
                            <button wire:click="updateQty({{ $item['product_id'] }}, {{ $item['quantity'] + 1 }})"
                                class="px-3 py-2 text-gray-500 hover:bg-gray-50 font-bold transition">+</button>
                        </div>

                        {{-- Line Total --}}
                        <div class="text-right shrink-0 w-24">
                            <p class="font-black text-gray-900 text-sm">ETB {{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                        </div>

                        {{-- Remove --}}
                        <button wire:click="removeItem({{ $item['product_id'] }})"
                            class="text-gray-300 hover:text-red-500 transition shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Continue Shopping --}}
            <a href="{{ route('store.products') }}" class="inline-flex items-center gap-2 text-sm text-purple-600 hover:text-purple-800 font-semibold transition">
                ← Continue Shopping
            </a>
        </div>

        {{-- Order Form --}}
        <div class="space-y-4">

            {{-- Summary --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-black text-gray-900 mb-4">Order Summary</h2>
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Subtotal ({{ $summary['count'] }} items)</span>
                    <span class="font-bold">ETB {{ number_format($summary['subtotal'], 2) }}</span>
                </div>
                <div class="border-t border-gray-100 pt-3 mt-3 flex justify-between font-black text-lg">
                    <span>Total</span>
                    <span class="text-purple-700">ETB {{ number_format($summary['subtotal'], 2) }}</span>
                </div>
            </div>

            {{-- Customer Details Form --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-black text-gray-900 mb-5">Your Details</h2>
                <div class="space-y-4">

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Full Name *</label>
                        <input wire:model.live="customerName" type="text" placeholder="e.g. Ahmed Mohammed"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-100 outline-none transition">
                        @error('customerName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Phone Number *</label>
                        <input wire:model.live="customerPhone" type="tel" placeholder="e.g. 0912345678"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-100 outline-none transition">
                        @error('customerPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Email (optional)</label>
                        <input wire:model.live="customerEmail" type="email" placeholder="your@email.com"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-100 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Delivery Address *</label>
                        <textarea wire:model.live="address" rows="2" placeholder="Your full delivery address"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-100 outline-none transition resize-none"></textarea>
                        @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Notes (optional)</label>
                        <textarea wire:model.live="notes" rows="2" placeholder="Any special instructions..."
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500 focus:ring-2 focus:ring-purple-100 outline-none transition resize-none"></textarea>
                    </div>

                    {{-- Error message --}}
                    @if($errorMessage)
                    <div class="p-4 bg-red-50 border-2 border-red-200 rounded-xl text-red-700 text-sm font-semibold">
                        ❌ {{ $errorMessage }}
                    </div>
                    @endif

                    <button wire:click="placeOrder" type="button"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-not-allowed"
                        class="w-full py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700
                               text-white font-black text-lg rounded-xl transition shadow-lg hover:shadow-xl active:scale-95">
                        <span wire:loading.remove wire:target="placeOrder">✅ Place Order</span>
                        <span wire:loading wire:target="placeOrder">Placing Order...</span>
                    </button>

                    <p class="text-xs text-gray-400 text-center">No account needed. We'll call you to confirm.</p>
                </div>
            </div>

        </div>
    </div>
    @endif

</div>
</div>
