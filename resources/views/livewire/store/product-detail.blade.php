<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <a href="{{ route('store.products') }}" class="hover:text-indigo-600 transition">Products</a>
        @if($product->category)
        <span>/</span>
        <span class="text-gray-600 font-medium">{{ $product->category->name }}</span>
        @endif
        <span>/</span>
        <span class="text-gray-600 font-medium truncate max-w-[180px]">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

        {{-- Image --}}
        <div class="space-y-3">
            <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl overflow-hidden flex items-center justify-center relative">
                @php
                    $imagePath = null;
                    if ($product->primaryImage) {
                        // Handle both array and object access
                        $imagePath = is_array($product->primaryImage) 
                            ? ($product->primaryImage['path'] ?? null)
                            : ($product->primaryImage->path ?? null);
                    }
                @endphp
                @if($imagePath)
                    <img src="{{ asset('storage/' . $imagePath) }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="flex flex-col items-center text-gray-300">
                        <svg class="w-24 h-24 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm text-gray-400">No Image Available</span>
                    </div>
                @endif

                @if($product->is_featured)
                <div class="absolute top-3 left-3">
                    <span class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full shadow">⭐ Featured</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Info --}}
        <div class="flex flex-col gap-5">

            {{-- Category badge --}}
            <div class="flex items-center gap-2 flex-wrap">
                @if($product->category)
                <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg">
                    {{ $product->category->name }}
                </span>
                @endif
            </div>

            {{-- Name --}}
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight">{{ $product->name }}</h1>

            {{-- Description --}}
            @if($product->description)
            <p class="text-gray-600 text-sm leading-relaxed">{{ $product->description }}</p>
            @endif

            {{-- Package size / unit --}}
            @if($product->package_size || $product->unit)
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                @if($product->package_size)
                <dt class="text-gray-500">Package Size</dt>
                <dd class="text-gray-900 font-medium">{{ $product->package_size }}</dd>
                @endif
                @if($product->unit)
                <dt class="text-gray-500">Unit</dt>
                <dd class="text-gray-900 font-medium">{{ $product->unit }}</dd>
                @endif
            </dl>
            @endif

            {{-- Price --}}
            <div class="flex items-baseline gap-3">
                <span class="text-3xl font-extrabold text-indigo-700">
                    ETB {{ number_format($product->selling_price, 2) }}
                </span>
                @if($product->unit)
                <span class="text-sm text-gray-400">/ {{ $product->unit }}</span>
                @endif
            </div>

            {{-- Cart actions --}}
            <div class="flex flex-col sm:flex-row gap-3">

                {{-- Quantity --}}
                <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                    <button onclick="document.getElementById('quantity').value = Math.max(1, parseInt(document.getElementById('quantity').value) - 1);"
                        class="px-3 py-2.5 text-gray-600 hover:bg-gray-50 transition font-bold text-lg leading-none">−</button>
                    <input id="quantity" type="number" value="1" min="1" step="1"
                        class="w-14 text-center text-sm font-semibold border-x border-gray-300 py-2.5 focus:outline-none">
                    <button onclick="document.getElementById('quantity').value = parseInt(document.getElementById('quantity').value) + 1;"
                        class="px-3 py-2.5 text-gray-600 hover:bg-gray-50 transition font-bold text-lg leading-none">+</button>
                </div>

                {{-- Add to cart --}}
                <button onclick="addProductToCart({{ $product->id }}, this)"
                    class="flex-1 flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition text-sm shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    🛒 Add to Cart
                </button>

                {{-- Wishlist --}}
                <button wire:click="toggleWishlist"
                    class="flex items-center justify-center gap-1.5 px-4 py-3 border rounded-xl transition text-sm font-semibold
                        {{ $inWishlist ? 'border-red-300 text-red-500 bg-red-50 hover:bg-red-100' : 'border-gray-300 text-gray-500 hover:border-red-300 hover:text-red-400' }}">
                    <svg class="w-5 h-5" fill="{{ $inWishlist ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span class="hidden sm:inline">{{ $inWishlist ? 'Saved' : 'Save' }}</span>
                </button>
            </div>

            {{-- Feedback message --}}
            @if($addedMessage)
            <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium
                {{ str_starts_with($addedMessage, 'Added') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                {{ $addedMessage }}
                @if(str_starts_with($addedMessage, 'Added'))
                <a href="{{ route('store.cart') }}" class="ml-auto underline font-bold text-emerald-800 whitespace-nowrap">View Cart →</a>
                @endif
            </div>
            @endif

        </div>
    </div>

    {{-- Related Products --}}
    @if($related->isNotEmpty())
    <section class="mt-16">
        <h2 class="text-xl font-bold text-gray-900 mb-5">You may also like</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($related as $rel)
            <a href="{{ route('store.product', $rel->id) }}"
               class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md hover:border-purple-300 transition group">
                <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center overflow-hidden">
                    @php
                        $relImagePath = null;
                        if ($rel->primaryImage) {
                            $relImagePath = is_array($rel->primaryImage) 
                                ? ($rel->primaryImage['path'] ?? null)
                                : ($rel->primaryImage->path ?? null);
                        }
                    @endphp
                    @if($relImagePath)
                        <img src="{{ asset('storage/' . $relImagePath) }}"
                             alt="{{ $rel->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    @else
                        <span class="text-4xl opacity-40">📦</span>
                    @endif
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-sm text-gray-900 group-hover:text-purple-700 transition leading-snug truncate">
                        {{ $rel->name }}
                    </h3>
                    <p class="text-purple-700 font-bold mt-1 text-sm">ETB {{ number_format($rel->selling_price, 2) }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

</div>

<script>
function addProductToCart(productId, button) {
    const quantityInput = document.getElementById('quantity');
    const quantity = parseInt(quantityInput.value) || 1;
    const originalText = button.innerHTML;
    
    button.disabled = true;
    button.style.opacity = '0.7';
    button.innerHTML = '⏳ Adding...';
    
    setTimeout(() => {
        window.location.href = "{{ url('/guest/cart/add') }}?product_id=" + productId + "&quantity=" + quantity;
    }, 600);
}
</script>
