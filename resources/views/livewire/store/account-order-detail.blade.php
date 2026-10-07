<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <a href="{{ route('store.account.orders') }}" class="hover:text-indigo-600 transition">My Orders</a>
        <span>/</span>
        <span class="text-gray-600 font-medium font-mono">{{ $order->order_number }}</span>
    </nav>

    {{-- Account nav --}}
    @include('livewire.store.partials.account-nav')

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 font-mono">{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Placed {{ optional($order->placed_at)->format('F d, Y \a\t g:i A') }}</p>
        </div>
        <span class="self-start sm:self-auto px-3 py-1.5 text-sm font-bold rounded-xl
            @if($order->status === 'completed') bg-emerald-100 text-emerald-700
            @elseif($order->status === 'confirmed') bg-indigo-100 text-indigo-700
            @elseif($order->status === 'cancelled') bg-red-100 text-red-700
            @else bg-amber-100 text-amber-700 @endif">
            {{ $order->getStatusLabel() }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Items + totals --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Order items --}}
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-900">Items ({{ $order->items->count() }})</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                    <div class="px-5 py-4 flex gap-4 items-start">
                        {{-- Product image --}}
                        <a href="{{ route('store.product', optional($item->product)->id ?? 0) }}"
                           class="shrink-0 w-14 h-14 bg-gray-50 rounded-xl overflow-hidden flex items-center justify-center border border-gray-100">
                            @if(optional($item->product)->primaryImage)
                                <img src="{{ asset('storage/'.optional($item->product)->primaryImage->path) }}"
                                     alt="{{ optional($item->product)->name }}"
                                     class="w-full h-full object-cover">
                            @else
                                <span class="text-xl">{{ optional($item->product)->type === 'book' ? '📚' : '🫗' }}</span>
                            @endif
                        </a>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('store.product', optional($item->product)->id ?? 0) }}"
                               class="font-semibold text-sm text-gray-900 hover:text-indigo-700 transition leading-snug block truncate">
                                {{ optional($item->product)->name ?? 'Product' }}
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ optional(optional($item->product)->category)->name }}
                            </p>

                            {{-- Type-specific detail --}}
                            @if(optional($item->product)->type === 'book')
                            <p class="text-xs text-gray-500 mt-1">📚 Book</p>
                            @elseif(optional($item->product)->package_size)
                            <p class="text-xs text-gray-500 mt-1">{{ $item->product->package_size }}</p>
                            @endif
                        </div>

                        {{-- Price --}}
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-bold text-gray-900">{{ number_format($item->total_amount, 2) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ number_format($item->quantity, 0) }} × {{ number_format($item->unit_price, 2) }}
                            </p>
                            @if($item->discount_amount > 0)
                            <p class="text-xs text-emerald-600 mt-0.5">−{{ number_format($item->discount_amount, 2) }} off</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 space-y-2">
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
                    <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-gray-900">
                        <span>Total</span>
                        <span class="text-indigo-700 text-base">{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment history --}}
            @if($order->invoice)
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-900">Payment</h2>
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full
                        {{ $order->invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ ucfirst($order->invoice->status) }}
                    </span>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Invoice</p>
                            <p class="font-mono font-semibold text-gray-900 mt-0.5">{{ $order->invoice->invoice_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Balance Due</p>
                            <p class="font-bold {{ $order->invoice->getBalanceDue() > 0 ? 'text-red-600' : 'text-emerald-600' }} mt-0.5">
                                {{ number_format($order->invoice->getBalanceDue(), 2) }}
                            </p>
                        </div>
                    </div>

                    @if($order->invoice->payments->isNotEmpty())
                    <div class="border-t border-gray-100 pt-3 space-y-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Payments Received</p>
                        @foreach($order->invoice->payments as $payment)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                                <span class="text-gray-700 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                                <span class="text-gray-400 text-xs">{{ optional($payment->payment_date)->format('M d, Y') }}</span>
                            </div>
                            <span class="font-semibold text-emerald-600">{{ number_format($payment->amount, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Delivery + actions --}}
        <div class="space-y-4">

            {{-- Delivery status --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="font-bold text-gray-900 mb-4">Delivery</h2>
                @if($order->deliveries->isNotEmpty())
                @php $delivery = $order->deliveries->first(); @endphp
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                            {{ $delivery->status === 'delivered' ? 'bg-emerald-100 text-emerald-600' : 'bg-indigo-100 text-indigo-600' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $delivery->status) }}</p>
                            @if($delivery->delivery_date)
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($delivery->delivery_date)->format('M d, Y') }}</p>
                            @endif
                        </div>
                    </div>
                    @if($delivery->notes)
                    <p class="text-xs text-gray-500 bg-gray-50 rounded-lg p-2.5">{{ $delivery->notes }}</p>
                    @endif
                </div>
                @else
                <p class="text-sm text-gray-500">Delivery not yet scheduled. Our staff will contact you.</p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-5 space-y-3">
                <a href="{{ route('store.account.orders') }}"
                   class="flex items-center justify-center gap-2 w-full py-2.5 border border-gray-300 text-gray-700 hover:border-indigo-400 hover:text-indigo-700 font-semibold text-sm rounded-xl transition">
                    ← Back to Orders
                </a>
                <a href="{{ route('store.products') }}"
                   class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>

</div>
