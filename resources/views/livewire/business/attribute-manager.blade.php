<div>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Dynamic Attributes</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Define custom attributes that products in specific categories must have.
                </p>
            </div>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="shadow sm:rounded-md sm:overflow-hidden bg-white px-4 py-5 sm:p-6 space-y-6">
                
                @if (session()->has('success'))
                    <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Select Category</label>
                    <select wire:model.live="selectedCategoryId" id="category" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">-- Choose Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedCategoryId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                @if($selectedCategoryId)
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Add New Attribute</h4>
                    <form wire:submit.prevent="saveAttribute" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div class="sm:col-span-1">
                            <label for="name" class="block text-sm font-medium text-gray-700">Attribute Name</label>
                            <input type="text" wire:model="name" id="name" placeholder="e.g. Color, Viscosity..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <label for="type" class="block text-sm font-medium text-gray-700">Input Type</label>
                            <select wire:model="type" id="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="text">Text / String</option>
                                <option value="select">Dropdown / Select</option>
                                <option value="boolean">Yes/No (Boolean)</option>
                            </select>
                            @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Add Attribute
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Existing Attributes</h4>
                    @if(count($category_attributes) > 0)
                        <ul class="divide-y divide-gray-200 border rounded-md">
                            @foreach($category_attributes as $attr)
                                <li class="py-3 px-4 flex justify-between items-center bg-gray-50">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $attr->name }}</p>
                                        <p class="text-xs text-gray-500">Type: {{ ucfirst($attr->type) }}</p>
                                    </div>
                                    <button wire:click="deleteAttribute({{ $attr->id }})" wire:confirm="Are you sure?" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Delete
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">No attributes defined for this category.</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
