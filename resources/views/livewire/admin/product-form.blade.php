<div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Create New Product</h2>

    @if (session()->has('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        
        <!-- Category Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category (Triggers Dynamic Fields)</label>
            <select wire:model.live="categoryId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select a Category...</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('categoryId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            <!-- Core Fields -->
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name</label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU</label>
                <input type="text" wire:model="sku" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barcode</label>
                <input type="text" wire:model="barcode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Initial Stock Quantity</label>
                <input type="number" wire:model="initialStock" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        <!-- Dynamic Fields Container -->
        @if(count($dynamicAttributes) > 0)
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Dynamic Attributes for Category</h3>
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    @foreach($dynamicAttributes as $attr)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $attr->name }}
                                @if($attr->is_required) <span class="text-red-500">*</span> @endif
                            </label>
                            
                            @if($attr->type === 'text')
                                <input type="text" wire:model="attributeValues.{{ $attr->slug }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            
                            @elseif($attr->type === 'number')
                                <input type="number" wire:model="attributeValues.{{ $attr->slug }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            
                            @elseif($attr->type === 'boolean')
                                <div class="mt-2 flex items-center">
                                    <input type="checkbox" wire:model="attributeValues.{{ $attr->slug }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Yes / No</span>
                                </div>
                            
                            @else
                                <input type="text" wire:model="attributeValues.{{ $attr->slug }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @endif
                            
                            @error('attributeValues.'.$attr->slug) 
                                <span class="text-red-500 text-xs">{{ $message }}</span> 
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-6 flex justify-end">
            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Product
            </button>
        </div>
    </form>
</div>
