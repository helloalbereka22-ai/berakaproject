<div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-6 p-4">
    <!-- Left Column: Search & Catalog -->
    <div class="w-full lg:w-2/3 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Point of Sale</h2>

        @if (session()->has('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Product Search -->
        <div class="mb-6 relative">
            <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Search products by name, SKU, or scan barcode..." 
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg py-3 px-4">
            
            <!-- Search Results Dropdown -->
            @if(!empty($availableProducts))
            <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm">
                @foreach($availableProducts as $inventory)
                    <div wire:click="addToCart({{ $inventory->id }}, '{{ $inventory->name }}', {{ $inventory->attributes_data['price'] ?? $inventory->selling_price }}, {{ $inventory->quantity_on_hand }})" 
                         class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 dark:hover:bg-gray-600">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $inventory->name }}</span>
                            <span class="text-gray-500 dark:text-gray-300">${{ number_format($inventory->attributes_data['price'] ?? $inventory->selling_price, 2) }} ({{ $inventory->quantity_on_hand }} in stock)</span>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Cart -->
    <div class="w-full lg:w-1/3 bg-white dark:bg-gray-800 shadow rounded-lg p-6 flex flex-col h-[calc(100vh-10rem)]">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Order</h3>
        
        <!-- Customer Selection -->
        <div class="mb-4">
            <select wire:model="customerId" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Walk-in Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto mb-4 border-t border-b border-gray-200 dark:border-gray-700 py-2">
            @forelse($cart as $id => $item)
                <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</h4>
                        <p class="text-sm text-gray-500">${{ number_format($item['price'], 2) }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="number" wire:model.live.debounce.500ms="cart.{{$id}}.quantity" min="1" class="w-16 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm text-center p-1">
                        <button wire:click="removeFromCart({{ $id }})" class="text-red-500 hover:text-red-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    Cart is empty. Scan or search to add products.
                </div>
            @endforelse
        </div>

        <!-- Totals & Actions -->
        <div>
            <div class="flex justify-between py-2">
                <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                <span class="font-medium dark:text-white">${{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 border-t border-gray-200 dark:border-gray-700 mt-2">
                <span class="text-lg font-bold text-gray-900 dark:text-white">Total:</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($total, 2) }}</span>
            </div>
            
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button wire:click="checkout(app(\App\Services\SalesService::class), 'quotation')" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Save Quote
                </button>
                <button wire:click="checkout(app(\App\Services\SalesService::class), 'completed_sale')" class="bg-green-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-green-700">
                    Pay / Checkout
                </button>
            </div>
        </div>
    </div>
</div>
