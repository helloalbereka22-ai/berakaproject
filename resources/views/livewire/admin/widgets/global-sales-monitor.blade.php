<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Global Sales Monitor
        </h3>
        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Detailed Report</a>
    </div>
    
    <div class="p-5 grid grid-cols-3 gap-4 border-b border-gray-100 dark:border-gray-700">
        <div class="text-center">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Today</p>
            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">${{ number_format($salesData['today'], 2) }}</p>
        </div>
        <div class="text-center border-l border-r border-gray-100 dark:border-gray-700">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">This Week</p>
            <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">${{ number_format($salesData['this_week'], 2) }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">This Month</p>
            <p class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($salesData['this_month'], 2) }}</p>
        </div>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0">
        <ul class="divide-y divide-gray-100 dark:divide-gray-750">
            @forelse($recentOrders as $order)
                <li class="px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Customer: {{ $order->customer->name ?? 'Unknown' }} &bull; {{ $order->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">${{ number_format($order->total_amount, 2) }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Paid</span>
                    </div>
                </li>
            @empty
                <li class="px-5 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No sales recorded yet.
                </li>
            @endforelse
        </ul>
    </div>
</div>
