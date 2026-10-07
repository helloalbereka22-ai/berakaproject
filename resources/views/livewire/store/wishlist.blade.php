<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
        <a href="{{ route('store.index') }}" class="hover:text-indigo-600 transition">Home</a>
        <span>/</span>
        <span class="text-gray-600 font-medium">Wishlist</span>
    </nav>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-extrabold text-gray-900">
            Wishlist
            @if($products->isNotEmpty())
            <span class="text-base font-normal text-gray-400 ml-2">{{ $products->count() }} saved</span>
            @endif
        </h1>
        @if($products->isNotEmpty())
        <a href="{{ route('store.products') }}"
           class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition">
            Continue Shopping →
        </a>
        @endif
    </div>

    @if($products->isEmpty())
    {{-- Empty wishlist --}}
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-700 mb-2">Your wishlist is empty</h2>
        <p class="text-gray-500 text-sm mb-6 max-w-xs">Save products you love by tapping the heart icon on any product.</p>
        <a href="{{ route('store.products') }}"
           class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition text-sm">
            Explore Products
        </a>
    </div>

    @else

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach($products as $product)
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md hover:border-indigo-300 transition group flex flex-col">

            {{-- Image --}}
            <a href="{{ route('store.product', $product->id) }}" class="block relative">
                <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center overflow-hidden">
                    @if($product->primaryImage)
                        <img src="{{ asset('storage/'.$product->primaryImage->path) }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    @else
                        <span class="text-4xl opacity-40">
                            {{ $product->type === 'book' ? '📚' : '🫗' }}
                        </span>
                    @endif
                </div>

                {{-- Type badge --}}
                <span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-bold rounded-full
                    {{ $product->type === 'book' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $product->type === 'book' ? '📚' : '🫗' }}
                </span>
            </a>

            {{-- Info --}}
            <div class="p-3 flex-1 flex flex-col">
                <a href="{{ route('store.product', $product->id) }}" class="block">
                    <p class="text-xs text-gray-400 truncate">{{ optional($product->category)->name }}</p>
                    <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-700 transition mt-0.5">
                        {{ $product->name }}
                    </h3>
                    <p class="font-bold text-indigo-700 mt-2 text-sm">{{ number_format($product->selling_price, 2) }}</p>
                </a>

                {{-- Actions --}}
                <div class="flex gap-2 mt-3">
                    {{-- Move to cart --}}
                    <button wire:click="moveToCart({{ $product->id }})"
                        wire:loading.attr="disabled"
                        class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1">
                        <svg wire:loading.remove wire:target="moveToCart({{ $product->id }})" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <svg wire:loading wire:target="moveToCart({{ $product->id }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Add to Cart
                    </button>

                    {{-- Remove from wishlist --}}
                    <button wire:click="removeFromWishlist({{ $product->id }})"
                        title="Remove from wishlist"
                        class="p-2 border border-gray-200 text-gray-400 hover:border-red-300 hover:text-red-500 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Flash messages from moveToCart --}}
    @if(session('error'))
    <div class="mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl">
        {{ session('error') }}
    </div>
    @endif

    @endif

</div>
