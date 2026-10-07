<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('business.customers') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $customer->name }}</h1>
                <p class="text-sm text-gray-500">Customer Profile</p>
            </div>
        </div>
        
        <div>
            @if($customer->is_active)
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
            @else
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Contact Information Card -->
        <div class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Contact Information</h3>
            </div>
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Email</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $customer->email ?? 'Not provided' }}</div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Phone</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $customer->phone ?? 'Not provided' }}</div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Address</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $customer->address ?? 'Not provided' }}</div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Tax Number</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $customer->tax_number ?? 'Not provided' }}</div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Customer Since</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $customer->created_at->format('F j, Y') }}</div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 border-t border-gray-200">
                <button type="button" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit Details</button>
            </div>
        </div>

        <!-- Recent Orders & Stats -->
        <div class="md:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white shadow rounded-lg border border-gray-200 p-5 flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="ml-4 text-left">
                        <p class="text-sm font-medium text-gray-500 truncate">Total Orders</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $customer->orders->count() }}</p>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg border border-gray-200 p-5 flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4 text-left">
                        <p class="text-sm font-medium text-gray-500 truncate">Total Lifetime Value</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format($customer->orders->sum('total_amount'), 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Orders</h3>
                    <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($customer->orders as $order)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                        #{{ $order->order_number ?? $order->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $order->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        ${{ number_format($order->total_amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 text-sm">
                                        This customer hasn't placed any orders yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
