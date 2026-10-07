<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Success header --}}
    <div class="text-center mb-10">
        <div class="w-20 h-20 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-5">
            <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Order Confirmed!</h1>
        <p class="text-gray-500">Thank you for your purchase. We'll contact you soon to arrange payment and delivery.</p>
    </div>

    {{-- Order number card --}}
    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl px-6 py-4 flex items-center justify-between mb-6">
        <div>
            <p class="text-xs text-indigo-500 font-semibold uppercase tracking-wider">Order Number</p>
            <p class="text-xl font-extrabold text-indigo-800 font-mono mt-0.5">{{ $order->order_number }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-indigo-500 font-semibold uppercase tracking-wider">Status</p>
            <span class="inline-block mt-1 px-3 py-1 text-xs font-bold rounded-full
                @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                @elseif($order->status === 'confirmed') bg-indigo-100 text-indigo-700
                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                @else bg-amber-100 text-amber-700 @endif">
                {{ $order->getStatusLabel() }}
            </span>
        </div>
    </div>

    {{-- Order items --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-900">Items Ordered</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($order->items as $item)
            <div class="px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 shrink-0 bg-gray-100 rounded-lg flex items-center justify-center text-base">
                        {{ optional($item->product)->type === 'book' ? '📚' : '🫗' }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ optional($item->product)->name ?? 'Product' }}</p>
                        <p class="text-xs text-gray-400">
                            {{ optional(optional($item->product)->category)->name }}
                            @if($item->discount_amount > 0)
                            · <span class="text-emerald-600">−{{ number_format($item->discount_amount, 2) }} off</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-bold text-gray-900">{{ number_format($item->total_amount, 2) }}</p>
                    <p class="text-xs text-gray-400">{{ number_format($item->quantity, 0) }} × {{ number_format($item->unit_price, 2) }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Totals --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 space-y-2">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Subtotal</span>
                <span>{{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="flex justify-between text-sm text-emerald-600">
                <span>Discount</span>
                <span>−{{ number_format($order->discount_amount, 2) }}</span>
            </div>
            @endif
            @if($order->delivery_fee > 0)
            <div class="flex justify-between text-sm text-gray-600">
                <span>Delivery fee</span>
                <span>{{ number_format($order->delivery_fee, 2) }}</span>
            </div>
            @endif
            <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-gray-900 text-base">
                <span>Total</span>
                <span class="text-indigo-700">{{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Invoice & Payment info --}}
    @if($order->invoice)
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
        <h2 class="font-bold text-gray-900 mb-4">Invoice & Payment</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-500 mb-1">Invoice Number</p>
                <p class="font-semibold font-mono text-gray-900">{{ $order->invoice->invoice_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Invoice Status</p>
                <span class="inline-block px-2 py-0.5 text-xs font-bold rounded-full
                    @if($order->invoice->status === 'paid') bg-emerald-100 text-emerald-700
                    @else bg-amber-100 text-amber-700 @endif">
                    {{ ucfirst($order->invoice->status) }}
                </span>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Balance Due</p>
                <p class="font-bold text-gray-900">{{ number_format($order->invoice->getBalanceDue(), 2) }}</p>
            </div>
        </div>
        <div class="mt-4 flex items-start gap-2.5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>Our staff will contact you to collect payment. Do not share your payment details unless you contacted us first.</span>
        </div>
    </div>
    @endif

    {{-- Delivery status --}}
    @if($order->deliveries->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
        <h2 class="font-bold text-gray-900 mb-4">Delivery</h2>
        @php $delivery = $order->deliveries->first(); @endphp
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</p>
                @if($delivery->delivery_date)
                <p class="text-xs text-gray-500">Expected: {{ \Carbon\Carbon::parse($delivery->delivery_date)->format('M d, Y') }}</p>
                @else
                <p class="text-xs text-gray-500">Date will be confirmed by our staff</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('store.account.orders') }}"
           class="flex-1 flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            View My Orders
        </a>
        <a href="{{ route('store.products') }}"
           class="flex-1 flex items-center justify-center gap-2 py-3 border border-gray-300 hover:border-indigo-400 text-gray-700 hover:text-indigo-700 font-bold rounded-xl transition text-sm">
            Continue Shopping
        </a>
    </div>

</div>
