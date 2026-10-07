<div class="space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.orders.index') }}" wire:navigate class="hover:text-slate-300 transition">Orders</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-300 font-mono">{{ $order->order_number }}</span>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $successMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errorMessage }}
        </div>
    @endif

    @php
        $statusColors = ['pending'=>'amber','confirmed'=>'sky','processing'=>'violet','completed'=>'emerald','cancelled'=>'red'];
        $payColors    = ['pending'=>'amber','approved'=>'emerald','rejected'=>'red'];
        $sc = $statusColors[$order->status]          ?? 'slate';
        $pc = $payColors[$order->payment_status]     ?? 'slate';
    @endphp

    {{-- Header row --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white font-mono">{{ $order->order_number }}</h2>
            <p class="text-sm text-slate-400 mt-1">
                Placed {{ $order->placed_at?->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}
                @if($order->branch) &mdash; {{ $order->branch->name }} @endif
            </p>
        </div>

        {{-- Action buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @if($order->canApprovePayment())
                <button wire:click="approvePayment"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition">
                    ✓ Approve Payment
                </button>
                <button wire:click="openRejectModal"
                    class="px-4 py-2 rounded-xl bg-red-600/20 hover:bg-red-600/40 text-red-400 text-sm font-bold border border-red-500/30 transition">
                    ✗ Reject Payment
                </button>
            @endif

            @if($order->canProcess())
                <button wire:click="processOrder"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold transition">
                    → Process Order
                </button>
            @endif

            @if($order->canComplete())
                <button wire:click="completeOrder"
                    wire:loading.attr="disabled"
                    wire:confirm="Complete this order? Inventory will be deducted now. This cannot be undone."
                    class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold transition">
                    ✓ Complete Order
                </button>
            @endif
        </div>
    </div>

    {{-- Status badges --}}
    <div class="flex flex-wrap gap-3">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
            bg-{{ $sc }}-500/10 text-{{ $sc }}-400 border border-{{ $sc }}-500/30">
            Order: {{ ucfirst($order->status) }}
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
            bg-{{ $pc }}-500/10 text-{{ $pc }}-400 border border-{{ $pc }}-500/30">
            Payment: {{ ucfirst($order->payment_status) }}
        </span>
        @if($order->payment_approved_at)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                bg-slate-800 text-slate-400 border border-slate-700">
                Approved {{ $order->payment_approved_at->format('d M H:i') }}
                @if($order->paymentApprovedBy) by {{ $order->paymentApprovedBy->name }} @endif
            </span>
        @endif
    </div>

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: order items (2 cols) --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Order Items --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-800/80">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wide">Order Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-800/60 bg-slate-800/20">
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Price</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Discount</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Line Total</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Branch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @foreach($order->items as $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-white text-xs">
                                        {{ optional($item->product)->name ?? '—' }}
                                    </div>
                                    <div class="text-slate-600 text-xs">
                                        {{ optional(optional($item->product)->category)->name }}
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">
                                    {{ number_format($item->quantity, 2) }}
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">
                                    {{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-xs">
                                    @if($item->discount_amount > 0)
                                        <span class="text-amber-400">-{{ number_format($item->discount_amount, 2) }}</span>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-bold font-mono text-white text-xs">
                                    {{ number_format($item->total_amount, 2) }} ETB
                                </td>
                                <td class="px-5 py-3 text-slate-400 text-xs">
                                    {{ optional($item->fulfillingBranch)->name ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-700">
                            <tr>
                                <td colspan="4" class="px-5 py-3 text-right text-slate-400 text-xs font-semibold uppercase tracking-wider">Subtotal</td>
                                <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">{{ number_format($order->subtotal, 2) }} ETB</td>
                                <td></td>
                            </tr>
                            @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="px-5 py-2 text-right text-amber-500 text-xs font-semibold uppercase tracking-wider">Discount</td>
                                <td class="px-5 py-2 text-right font-mono text-amber-400 text-xs">-{{ number_format($order->discount_amount, 2) }} ETB</td>
                                <td></td>
                            </tr>
                            @endif
                            @if($order->delivery_fee > 0)
                            <tr>
                                <td colspan="4" class="px-5 py-2 text-right text-slate-400 text-xs font-semibold uppercase tracking-wider">Delivery</td>
                                <td class="px-5 py-2 text-right font-mono text-slate-200 text-xs">{{ number_format($order->delivery_fee, 2) }} ETB</td>
                                <td></td>
                            </tr>
                            @endif
                            <tr class="bg-slate-800/30">
                                <td colspan="4" class="px-5 py-3 text-right text-white text-sm font-bold uppercase tracking-wider">Total</td>
                                <td class="px-5 py-3 text-right font-bold font-mono text-white">{{ number_format($order->total_amount, 2) }} ETB</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Payment History --}}
            @if($order->invoice && $order->invoice->payments->count())
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-800/80">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wide">Payment History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-800/60 bg-slate-800/20">
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Method</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @foreach($order->invoice->payments as $payment)
                            <tr>
                                <td class="px-5 py-3 text-slate-400 text-xs">{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td class="px-5 py-3 text-slate-200 text-xs capitalize">{{ $payment->payment_method }}</td>
                                <td class="px-5 py-3 text-slate-600 text-xs font-mono">{{ $payment->transaction_reference ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold font-mono text-white text-xs">{{ number_format($payment->amount, 2) }} ETB</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $payment->status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-700 text-slate-400 border border-slate-600' }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- Right: order meta --}}
        <div class="space-y-4">

            {{-- Customer --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Customer</h3>
                @if($order->customer)
                    <div class="font-semibold text-white text-sm">{{ $order->customer->name }}</div>
                    <div class="text-slate-400 text-xs mt-0.5">{{ $order->customer->email }}</div>
                    <div class="text-slate-400 text-xs mt-0.5">{{ $order->customer->phone ?? '—' }}</div>
                @else
                    <p class="text-slate-600 text-sm">No customer linked.</p>
                @endif
            </div>

            {{-- Branch --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Fulfilling Branch</h3>
                @if($order->branch)
                    <div class="font-semibold text-white text-sm">{{ $order->branch->name }}</div>
                    <div class="text-slate-400 text-xs mt-0.5">{{ optional($order->branch->business)->name }}</div>
                @else
                    <p class="text-slate-600 text-sm">—</p>
                @endif
            </div>

            {{-- Invoice --}}
            @if($order->invoice)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Invoice</h3>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400 text-xs">Number</span>
                    <span class="font-mono text-white text-xs">{{ $order->invoice->invoice_number }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400 text-xs">Status</span>
                    <span class="text-xs font-semibold
                        {{ $order->invoice->status === 'paid' ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ ucfirst($order->invoice->status) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400 text-xs">Total</span>
                    <span class="font-bold font-mono text-white text-xs">{{ number_format($order->invoice->total_amount, 2) }} ETB</span>
                </div>
                @php $paid = $order->invoice->payments->where('status','completed')->sum('amount'); @endphp
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400 text-xs">Paid</span>
                    <span class="font-mono text-xs text-emerald-400">{{ number_format($paid, 2) }} ETB</span>
                </div>
                @if($order->invoice->total_amount - $paid > 0.001)
                <div class="flex justify-between text-sm border-t border-slate-800 pt-2 mt-1">
                    <span class="text-slate-400 text-xs">Balance Due</span>
                    <span class="font-bold font-mono text-xs text-red-400">{{ number_format($order->invoice->total_amount - $paid, 2) }} ETB</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Delivery --}}
            @if($order->deliveries->count())
            @php $delivery = $order->deliveries->first(); @endphp
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Delivery</h3>
                <div class="flex justify-between mb-1">
                    <span class="text-slate-400 text-xs">Status</span>
                    <span class="text-xs font-semibold text-slate-200 capitalize">{{ $delivery->status }}</span>
                </div>
                @if($delivery->delivery_date)
                <div class="flex justify-between">
                    <span class="text-slate-400 text-xs">Date</span>
                    <span class="text-xs text-slate-200">{{ $delivery->delivery_date->format('d M Y') }}</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Notes --}}
            @if($order->notes)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Notes</h3>
                <p class="text-slate-300 text-xs leading-relaxed whitespace-pre-wrap">{{ $order->notes }}</p>
            </div>
            @endif

        </div>
    </div>

    {{-- Reject Payment Modal --}}
    @if($showRejectModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-white">Reject Payment</h3>
            <p class="text-slate-400 text-sm">
                Provide a reason for rejecting the payment on order
                <span class="font-mono text-white">{{ $order->order_number }}</span>.
            </p>
            <textarea wire:model="rejectReason"
                rows="3"
                placeholder="e.g. Payment proof not received, incorrect amount…"
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 placeholder-slate-600 outline-none focus:border-red-500 resize-none">
            </textarea>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('showRejectModal', false)"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">
                    Cancel
                </button>
                <button wire:click="confirmReject"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold transition">
                    Confirm Rejection
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
