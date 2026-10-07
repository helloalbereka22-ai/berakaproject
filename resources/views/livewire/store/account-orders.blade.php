<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <span class="text-gray-600 font-medium">My Orders</span>
    </nav>

    {{-- Account nav --}}
    @include('livewire.store.partials.account-nav')

    <h1 class="text-2xl font-extrabold text-gray-900 mb-6">My Orders</h1>

    @if($orders->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center bg-white border border-gray-200 rounded-2xl">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <h2 class="text-lg font-bold text-gray-700 mb-2">No orders yet</h2>
        <p class="text-gray-500 text-sm mb-6">Start shopping to see your orders here.</p>
        <a href="{{ route('store.products') }}"
           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm">
            Shop Now
        </a>
    </div>
    @else

    <div class="space-y-4">
        @foreach($orders as $order)
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-gray-300 transition">

            {{-- Order header --}}
            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-bold font-mono text-gray-900 text-sm">{{ $order->order_number }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full
                        @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                        @elseif($order->status === 'confirmed') bg-indigo-100 text-indigo-700
                        @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                        @else bg-amber-100 text-amber-700 @endif">
                        {{ $order->getStatusLabel() }}
                    </span>
                    @if($order->invoice)
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full
                        {{ $order->invoice->status === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                        {{ $order->invoice->status === 'paid' ? '✓ Paid' : 'Awaiting Payment' }}
                    </span>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-gray-400">{{ optional($order->placed_at)->format('M d, Y') }}</p>
                        <p class="font-bold text-indigo-700 text-sm">{{ number_format($order->total_amount, 2) }}</p>
                    </div>
                    <a href="{{ route('store.account.order', $order->order_number) }}"
                       class="px-4 py-2 border border-gray-300 text-gray-700 hover:border-indigo-400 hover:text-indigo-700 text-xs font-semibold rounded-xl transition whitespace-nowrap">
                        View Details
                    </a>
                </div>
            </div>

            {{-- Items preview --}}
            <div class="px-6 py-3 flex items-center gap-3 flex-wrap">
                @foreach($order->items->take(3) as $item)
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span class="w-7 h-7 bg-gray-100 rounded-lg flex items-center justify-center text-base shrink-0">
                        {{ optional($item->product)->type === 'book' ? '📚' : '🫗' }}
                    </span>
                    <span class="truncate max-w-[120px]">{{ optional($item->product)->name ?? 'Item' }}</span>
                    <span class="text-gray-400 text-xs shrink-0">×{{ number_format($item->quantity, 0) }}</span>
                </div>
                @endforeach
                @if($order->items->count() > 3)
                <span class="text-xs text-gray-400">+{{ $order->items->count() - 3 }} more</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
    @endif

</div>
