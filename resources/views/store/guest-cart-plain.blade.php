<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — Al-Baraka</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-10">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
@php $old = $old ?? []; @endphp

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('store.products') }}" class="text-gray-400 hover:text-purple-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-black text-gray-900">🛒 Your Cart</h1>
        {{-- Nav links --}}
        <div class="ml-auto flex gap-4 text-sm">
            <a href="{{ route('home') }}" class="text-gray-500 hover:text-purple-600 font-semibold">Home</a>
            <a href="{{ route('store.products') }}" class="text-gray-500 hover:text-purple-600 font-semibold">Products</a>
            <a href="{{ route('manufacturing') }}" class="text-gray-500 hover:text-purple-600 font-semibold">Manufacturing</a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-4 mb-6 text-green-700 font-semibold">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-4 mb-6 text-red-700 font-semibold">
        @foreach($errors->all() as $error)
        <p>❌ {{ $error }}</p>
        @endforeach
    </div>
    @endif

    @if(empty($summary['items']))
    {{-- Empty Cart --}}
    <div class="bg-white rounded-3xl shadow-xl p-10 text-center">
        <div class="text-6xl mb-4">🛒</div>
        <h2 class="text-2xl font-black text-gray-800 mb-3">Your cart is empty</h2>
        <p class="text-gray-500 mb-8">Browse our products and add items to your cart.</p>
        <a href="{{ route('store.products') }}"
           class="inline-block px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-black rounded-xl">
            Browse Products
        </a>
    </div>

    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cart Items --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-black text-gray-900">{{ $summary['count'] }} {{ $summary['count'] === 1 ? 'Item' : 'Items' }}</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($summary['items'] as $item)
                    <div class="flex items-center gap-4 p-4">
                        {{-- Image --}}
                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 shrink-0 flex items-center justify-center">
                            @if(!empty($item['image']))
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-2xl text-gray-300">📦</span>
                            @endif
                        </div>
                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 text-sm truncate">{{ $item['name'] }}</p>
                            <p class="text-purple-600 font-black">ETB {{ number_format($item['price'], 2) }}</p>
                        </div>
                        {{-- Qty --}}
                        <div class="text-center">
                            <p class="text-xs text-gray-500 mb-1">Qty</p>
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                <a href="{{ url('/guest/cart/update') }}?product_id={{ $item['product_id'] }}&qty={{ $item['quantity'] - 1 }}"
                                   class="px-3 py-1.5 text-gray-500 hover:bg-gray-50 font-bold text-sm">−</a>
                                <span class="px-3 py-1.5 text-sm font-bold border-x border-gray-200">{{ $item['quantity'] }}</span>
                                <a href="{{ url('/guest/cart/update') }}?product_id={{ $item['product_id'] }}&qty={{ $item['quantity'] + 1 }}"
                                   class="px-3 py-1.5 text-gray-500 hover:bg-gray-50 font-bold text-sm">+</a>
                            </div>
                        </div>
                        {{-- Total --}}
                        <div class="text-right shrink-0 w-24">
                            <p class="font-black text-gray-900 text-sm">ETB {{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                        </div>
                        {{-- Remove --}}
                        <a href="{{ url('/guest/cart/remove') }}?product_id={{ $item['product_id'] }}"
                           class="text-gray-300 hover:text-red-500 shrink-0">✕</a>
                    </div>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('store.products') }}" class="text-sm text-purple-600 hover:text-purple-800 font-semibold">
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
                <div class="border-t pt-3 mt-3 flex justify-between font-black text-lg">
                    <span>Total</span>
                    <span class="text-purple-700">ETB {{ number_format($summary['subtotal'], 2) }}</span>
                </div>
            </div>

            {{-- Order Form — plain HTML POST, no Livewire --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-black text-gray-900 mb-5">Your Details</h2>
                <form method="POST" action="{{ route('guest.cart.order') }}">
                    @csrf
                    <div class="space-y-4">

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1.5">Full Name *</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                placeholder="e.g. Ahmed Mohammed" required minlength="2"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-purple-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1.5">Phone Number *</label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}"
                                placeholder="e.g. 0912345678" required minlength="7"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-purple-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1.5">Email (optional)</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                                placeholder="your@email.com"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-purple-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1.5">Delivery Address *</label>
                            <textarea name="address" required minlength="5" rows="2"
                                placeholder="Your full delivery address"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-purple-500 outline-none resize-none">{{ old('address') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1.5">Notes (optional)</label>
                            <textarea name="notes" rows="2" placeholder="Any special instructions..."
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-purple-500 outline-none resize-none">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit"
                            class="w-full py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700
                                   text-white font-black text-lg rounded-xl transition shadow-lg">
                            ✅ Place Order
                        </button>

                        <p class="text-xs text-gray-400 text-center">No account needed. We'll call you to confirm.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
</body>
</html>
