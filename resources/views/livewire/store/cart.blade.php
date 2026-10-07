<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <span class="text-gray-600 font-medium">{{ __('app.cart.title') }}</span>
    </nav>

    <h1 class="text-2xl font-extrabold text-gray-900 mb-8">
        Shopping Cart
        @if($summary['item_count'] > 0)
        <span class="text-base font-normal text-gray-400 ml-2">{{ $summary['item_count'] }} {{ $summary['item_count'] === 1 ? 'item' : 'items' }}</span>
        @endif
    </h1>

    @if(empty($summary['items']))
    {{-- Empty cart --}}
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-700 mb-2">Your cart is empty</h2>
        <p class="text-gray-500 text-sm mb-6 max-w-xs">Looks like you haven't added anything yet. Discover our oils and books.</p>
        <a href="{{ route('store.products') }}"
           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm">
            Shop Now
        </a>
    </div>

    @else

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- ── Cart Items ───────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-3">

            @foreach($summary['items'] as $item)
            <div class="bg-white border border-gray-200 rounded-2xl p-4 flex gap-4 items-start transition hover:border-gray-300">

                {{-- Product image --}}
                <a href="{{ route('store.product', $item['product_id']) }}"
                   class="shrink-0 w-20 h-20 sm:w-24 sm:h-24 bg-gray-50 rounded-xl overflow-hidden flex items-center justify-center">
                    @php $cartProduct = $cartImages[$item['product_id']] ?? null; @endphp
                    @if($cartProduct?->primaryImage)
                        <img src="{{ asset('storage/'.$cartProduct->primaryImage->path) }}"
                             alt="{{ $item['product_name'] }}"
                             class="w-full h-full object-cover">
                    @else
                        <span class="text-3xl opacity-40">📦</span>
                    @endif
                </a>

                {{-- Details --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('store.product', $item['product_id']) }}"
                               class="font-semibold text-gray-900 text-sm hover:text-indigo-700 transition leading-snug line-clamp-2 block">
                                {{ $item['product_name'] }}
                            </a>
                            @if($item['business_name'])
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item['business_name'] }}</p>
                            @endif
                        </div>

                        {{-- Remove --}}
                        <button wire:click="removeItem({{ $item['product_id'] }})"
                            wire:confirm="Remove this item from your cart?"
                            class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                            title="Remove item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 mt-3">

                        {{-- Quantity --}}
                        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-sm">
                            <button wire:click="updateQuantity({{ $item['product_id'] }}, {{ max(0, $item['quantity'] - 1) }})"
                                class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-50 transition font-bold">−</button>
                            <span class="px-3 py-1.5 font-semibold text-gray-900 min-w-[2.5rem] text-center border-x border-gray-200">
                                {{ number_format($item['quantity'], 0) }}
                            </span>
                            <button wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['quantity'] + 1 }})"
                                class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-50 transition font-bold">+</button>
                        </div>

                        {{-- Price + discount --}}
                        <div class="text-right">
                            @if($item['discount_amount'] > 0)
                            <p class="text-xs text-gray-400 line-through">
                                {{ number_format($item['original_price'], 2) }}
                            </p>
                            @endif
                            <p class="font-bold text-indigo-700 text-sm">
                                {{ number_format($item['unit_price'], 2) }}
                                <span class="text-xs text-gray-400 font-normal">each</span>
                            </p>
                            <p class="text-xs font-semibold text-gray-900 mt-0.5">
                                = {{ number_format($item['line_total'], 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Continue shopping --}}
            <div class="pt-2">
                <a href="{{ route('store.products') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 font-medium transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Continue Shopping
                </a>
            </div>
        </div>

        {{-- ── Order Summary ─────────────────────────────────────────────── --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-24 space-y-5">
                <h2 class="font-bold text-gray-900 text-base">Order Summary</h2>

                {{-- Line totals --}}
                <div class="space-y-2">
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

                    <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-900">
                        <span>{{ __('app.cart.total') }}</span>
                        <span class="text-lg text-indigo-700">{{ number_format($summary['total'], 2) }}</span>
                    </div>
                </div>

                {{-- Discount code --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-2">Discount Code</label>
                    <div class="flex gap-2">
                        <input wire:model="discountCode" type="text" placeholder="Enter code"
                            class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:border-indigo-500 outline-none">
                        <button wire:click="applyDiscount"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition">
                            Apply
                        </button>
                    </div>
                    @if($discountMessage)
                    <p class="text-xs mt-2 font-medium {{ str_starts_with($discountMessage, 'Discount applied') ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $discountMessage }}
                    </p>
                    @endif
                </div>

                {{-- Checkout button --}}
                <button wire:click="checkout"
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
                    Proceed to Checkout
                </button>

                {{-- Trust signals --}}
                <div class="flex items-center justify-center gap-4 text-xs text-gray-400 pt-1">
                    <span class="flex items-center gap-1">🔒 Secure</span>
                    <span class="flex items-center gap-1">✅ Trusted</span>
                </div>
            </div>
        </div>

    </div>
    @endif

</div>
