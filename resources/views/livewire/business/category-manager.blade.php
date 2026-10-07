<div class="space-y-6 max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="md:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Create Category</h2>

        @if (session()->has('success'))
            <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-50">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="saveCategory" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input wire:model="name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea wire:model="description" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Parent Category</label>
                <select wire:model="parent_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">None (Root Category)</option>
                    @foreach(\App\Models\Category::all() as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('parent_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                    Save Category
                </button>
            </div>
        </form>
    </div>

    <!-- Tree View -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Category Tree</h2>

        <div class="space-y-2">
            @foreach($categories as $category)
                @include('livewire.catalog.partials.category-tree-item', ['category' => $category, 'level' => 0])
            @endforeach
        </div>
    </div>
</div>
