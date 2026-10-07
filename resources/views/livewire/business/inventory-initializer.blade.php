<div class="space-y-6 max-w-7xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">Inventory Initialization</h2>
            <p class="text-sm text-gray-500">Super Admin only: Initialize stock levels across all businesses.</p>
        </div>
        <div class="flex-1 flex gap-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search product..." class="block w-full sm:text-sm border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
            <select wire:model.live="filter_business" class="block w-full sm:text-sm border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Businesses</option>
                @foreach($businesses as $business)
                    <option value="{{ $business->id }}">{{ $business->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Level</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                            <div class="text-xs text-gray-500">SKU: {{ $product->sku ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($product->business)->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input wire:model.defer="opening.{{ $product->id }}" type="number" step="0.01" class="block w-24 sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input wire:model.defer="stock.{{ $product->id }}" type="number" step="0.01" class="block w-24 sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input wire:model.defer="min_level.{{ $product->id }}" type="number" step="0.01" class="block w-24 sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="saveInventory({{ $product->id }})" class="text-indigo-600 hover:text-indigo-900">Save</button>
                            @if (session()->has('success_' . $product->id))
                                <span class="ml-2 text-green-600 text-xs">Saved</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No products found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $products->links() }}
        </div>
    </div>
</div>
