<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Book Catalog</h1>
            <p class="text-sm text-gray-500">Browse and search books available to your branch.</p>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow border border-gray-200">
        <div class="relative max-w-lg w-full text-gray-400 focus-within:text-indigo-600">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by book title or SKU..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
    </div>

    <!-- Catalog Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse ($products as $product)
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                <div class="aspect-w-3 aspect-h-4 bg-gray-100 flex items-center justify-center p-4">
                    @if($product->media->isNotEmpty())
                        <img src="{{ $product->media->first()->file_path }}" alt="{{ $product->name }}" class="object-contain h-48 w-full mix-blend-multiply">
                    @else
                        <div class="h-48 w-full flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="text-xs">No Cover</span>
                        </div>
                    @endif
                </div>
                <div class="p-4 flex-1 flex flex-col">
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-indigo-600 mb-1 uppercase tracking-wide">
                            {{ $product->categories->first()->name ?? 'Uncategorized' }}
                        </p>
                        <h3 class="text-sm font-bold text-gray-900 line-clamp-2 leading-snug">
                            {{ $product->name }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">SKU: {{ $product->sku }}</p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-lg font-bold text-gray-900">${{ number_format($product->price, 2) }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-gray-500 bg-white rounded-lg shadow-sm border border-gray-200">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900">No books found</h3>
                <p class="mt-1 text-sm text-gray-500">There are no books assigned to your business yet or matching your search.</p>
            </div>
        @endforelse
    </div>

    @if($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
</div>
