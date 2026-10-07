<div>

{{-- Hero --}}
<div class="bg-gradient-to-br from-emerald-950 via-teal-950 to-gray-950 text-white py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-2xl">📚</div>
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest">{{ __('app.bookstore.subtitle') }}</span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold mb-5">{{ __('app.bookstore.title') }}</h1>
        <p class="text-emerald-100/80 text-lg leading-relaxed max-w-3xl">{{ __('app.bookstore.description') }}</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-8">
            <a href="{{ route('store.products', ['type' => 'book']) }}"
               class="inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl transition text-sm">
                {{ __('app.bookstore.browse_books') }}
            </a>
            <a href="{{ route('store.index') }}"
               class="inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-white/10 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/20 transition text-sm">
                {{ __('app.bookstore.visit_store') }}
            </a>
        </div>
    </div>
</div>

{{-- Book categories --}}
@if($bookCategories->isNotEmpty())
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-8 text-center">{{ __('app.bookstore.categories') }}</h2>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach($bookCategories as $cat)
            <a href="{{ route('store.products', ['categoryId' => $cat->id, 'type' => 'book']) }}"
               class="flex items-center gap-2 px-5 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 transition">
                @if($cat->icon)<span>{{ $cat->icon }}</span>@else<span>📖</span>@endif
                {{ $cat->getTranslatedName() }}
                <span class="text-xs text-gray-400">({{ $cat->book_count }})</span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured books --}}
@if($featuredBooks->isNotEmpty())
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900">{{ __('app.bookstore.featured') }}</h2>
            <a href="{{ route('store.products', ['type' => 'book']) }}"
               class="text-emerald-600 font-semibold text-sm hover:text-emerald-700 transition flex items-center gap-1">
                {{ __('app.product.view_all') }} <svg class="w-4 h-4 rtl-flip" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($featuredBooks as $book)
            @php
                $attrs = $book->attributeValues->keyBy(fn($v) => optional($v->attribute)->name ?? '');
                $author = optional($attrs->get('Author'))->value;
            @endphp
            <a href="{{ route('store.product', $book->id) }}"
               class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-emerald-300 hover:shadow-md transition">
                <div class="h-52 bg-gradient-to-br from-emerald-50 to-teal-50 flex items-center justify-center relative overflow-hidden">
                    @if($book->primaryImage)
                        <img src="{{ Storage::url($book->primaryImage->file_path) }}"
                             alt="{{ $book->name }}"
                             class="max-h-52 w-auto object-contain group-hover:scale-105 transition-transform duration-300"
                             loading="lazy">
                    @else
                        <div class="w-20 h-28 bg-emerald-100 rounded-lg shadow-md flex items-center justify-center">
                            <span class="text-4xl">📗</span>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <p class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 mb-1 group-hover:text-emerald-700 transition">{{ $book->name }}</p>
                    @if($author)<p class="text-xs text-gray-500 mb-2">{{ $author }}</p>@endif
                    <p class="text-sm font-bold text-emerald-700">{{ number_format($book->selling_price, 2) }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- New arrivals --}}
@if($newBooks->isNotEmpty())
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900">{{ __('app.bookstore.new_arrivals') }}</h2>
            <a href="{{ route('store.products', ['type' => 'book', 'sortBy' => 'newest']) }}"
               class="text-emerald-600 font-semibold text-sm hover:text-emerald-700 transition flex items-center gap-1">
                {{ __('app.product.view_all') }} <svg class="w-4 h-4 rtl-flip" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($newBooks as $book)
            @include('livewire.store.partials.product-card', ['product' => $book])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Collections --}}
@if($collections->isNotEmpty())
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold text-gray-900 mb-10">{{ __('app.bookstore.collections') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($collections as $col)
            <div class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-emerald-300 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl mb-4">📦</div>
                <h3 class="font-bold text-gray-900 mb-1">{{ $col->name }}</h3>
                <p class="text-xs text-gray-500 mb-4">{{ $col->products->count() }} books in collection</p>
                <div class="flex flex-wrap gap-1 mb-4">
                    @foreach($col->products->take(3) as $prod)
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded-md">{{ Str::limit($prod->name, 15) }}</span>
                    @endforeach
                </div>
                <a href="{{ route('store.index') }}"
                   class="block text-center py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition">
                    View Collection
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Branches --}}
@if($branches->isNotEmpty())
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold text-gray-900 mb-10">{{ __('app.bookstore.branches') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($branches as $branch)
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 hover:border-emerald-200 transition">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-lg shrink-0">📍</div>
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $branch->name }}</h3>
                        @if($branch->business)<p class="text-xs text-gray-500 mt-0.5">{{ $branch->business->name }}</p>@endif
                    </div>
                </div>
                @if($branch->description)<p class="text-sm text-gray-600 leading-relaxed">{{ $branch->description }}</p>@endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section class="py-20 bg-gradient-to-r from-emerald-700 to-teal-700 text-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-extrabold mb-4">Explore Our Full Collection</h2>
        <p class="text-emerald-200 mb-8">Thousands of books across all genres, available online and in-store.</p>
        <a href="{{ route('store.products', ['type' => 'book']) }}"
           class="inline-block px-8 py-4 bg-white text-emerald-800 font-bold rounded-xl hover:bg-emerald-50 transition shadow-lg">
            {{ __('app.bookstore.browse_books') }}
        </a>
    </div>
</section>

</div>
