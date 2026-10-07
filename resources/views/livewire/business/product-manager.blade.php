<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Top actions / Search and Filter -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name or SKU..." class="block w-full sm:text-sm border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="flex-1 flex gap-4">
            <select wire:model.live="filter_category" class="block w-full sm:text-sm border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filter_business" class="block w-full sm:text-sm border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Businesses</option>
                @foreach($businesses as $business)
                    <option value="{{ $business->id }}">{{ $business->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Create New Product</h2>

        @if (session()->has('success'))
            <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-50">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-3 mb-4 text-sm text-red-800 rounded-lg bg-red-50">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="saveProduct" class="space-y-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Core Fields -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business</label>
                    <select wire:model="business_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Select Business...</option>
                        @foreach($businesses as $business)
                            <option value="{{ $business->id }}">{{ $business->name }}</option>
                        @endforeach
                    </select>
                    @error('business_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select wire:model.live="category_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Select Category...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input wire:model="name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">SKU</label>
                    <input wire:model="sku" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Selling Price</label>
                    <input wire:model="selling_price" type="number" step="0.01" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cost Price</label>
                    <input wire:model="cost_price" type="number" step="0.01" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit of Measure</label>
                    <input wire:model="unit_of_measure" type="text" placeholder="e.g. pcs, kg, liters" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
            </div>

            <!-- Dynamic EAV Attributes -->
            @if(count($current_category_attributes) > 0)
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Category Attributes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($current_category_attributes as $attr)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ $attr->name }}</label>
                                @if($attr->type === 'boolean')
                                    <select wire:model="dynamic_attributes.{{ $attr->id }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Select --</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                @elseif($attr->type === 'select')
                                    <!-- Simple text input for now, could be dropdown if options were predefined -->
                                    <input wire:model="dynamic_attributes.{{ $attr->id }}" type="text" placeholder="Value..." class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @else
                                    <input wire:model="dynamic_attributes.{{ $attr->id }}" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                    Save Product
                </button>
            </div>
        </form>

        <div class="overflow-x-auto mt-8 border-t border-gray-200 pt-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }} <br><span class="text-xs text-gray-500">{{ $product->sku }}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($product->business)->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($product->category)->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->selling_price }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->stock_quantity }} {{ $product->unit_of_measure }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No products found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
