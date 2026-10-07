<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Assigned Products</h1>
        <div class="w-full sm:w-1/3 relative rounded-md shadow-sm">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search assigned products..." 
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div class="flex space-x-2">
            <!-- Buttons that would open modals linking to WorkflowService -->
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md shadow-sm transition">
                Request New Product
            </button>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($inventories as $inventory)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
                <div class="h-48 bg-gray-200 dark:bg-gray-700 relative">
                    <!-- Placeholder image -->
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400 dark:text-gray-500">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    
                    <!-- Stock Badge -->
                    @if($inventory->quantity_on_hand <= 0)
                        <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded bg-red-500 text-white shadow">Out of Stock</span>
                    @elseif($inventory->quantity_on_hand < 5)
                        <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded bg-yellow-500 text-white shadow">Low Stock</span>
                    @endif
                </div>
                
                <div class="p-4">
                    <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                        {{ $inventory->category->name ?? 'Uncategorized' }}
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate" title="{{ $inventory->name }}">
                        {{ $inventory->name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        SKU: {{ $inventory->sku ?? 'N/A' }}
                    </p>
                    
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">
                            ${{ number_format($inventory->selling_price ?? 0, 2) }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>{{ number_format($inventory->quantity_on_hand, 2) }}</strong> in stock
                        </span>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-750 px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <!-- Note this is a Request Stock button rather than an Edit button, enforcing permissions -->
                    <button class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-medium">
                        Request Stock
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-lg p-12 text-center shadow">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No products assigned</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You currently have no products with active inventory in this branch. 
                </p>
                <div class="mt-6">
                    <button class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Request Products from Super Admin
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $inventories->links() }}
    </div>
</div>
