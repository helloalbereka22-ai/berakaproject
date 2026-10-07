<div>

{{-- Header --}}
<div class="bg-gradient-to-br from-gray-900 to-indigo-950 text-white py-14">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold mb-6 text-center">{{ __('app.search_pub.title') }}</h1>
        <form method="GET" action="{{ route('search') }}" class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-indigo-300 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.400ms="q" type="text" name="q"
                    placeholder="{{ __('app.search_pub.placeholder') }}"
                    autofocus
                    class="w-full ps-12 pe-4 py-4 bg-white/10 border border-white/20 rounded-2xl text-white placeholder-indigo-300 focus:outline-none focus:border-indigo-400 focus:bg-white/15 transition text-sm">
            </div>
        </form>
        @if($query)
        <p class="text-center text-indigo-300 text-sm mt-4">
            {{ __('app.search_pub.results_for') }} "<span class="text-white font-semibold">{{ $query }}</span>" — {{ $totalCount }} {{ Str::plural('result', $totalCount) }}
        </p>
        @endif
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Loading --}}
    <div wire:loading class="text-center py-16">
        <svg class="w-8 h-8 animate-spin text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    <div wire:loading.remove>
        @if(!$query)
        <div class="text-center py-24 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p class="font-semibold text-gray-500">Enter a search term above to get started.</p>
        </div>
        @elseif($totalCount === 0)
        <div class="text-center py-24">
            <p class="text-gray-500">{{ str_replace(':query', $query, __('app.search_pub.no_results')) }}</p>
        </div>
        @else

        {{-- Products --}}
        @if($results['products']->isNotEmpty())
        <section class="mb-14">
            <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                <span class="text-xl">🛍</span>{{ __('app.search_pub.products') }}
                <span class="text-sm font-normal text-gray-400">({{ $results['products']->count() }})</span>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @foreach($results['products'] as $product)
                <a href="{{ route('store.product', $product->id) }}"
                   class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-indigo-300 hover:shadow-md transition text-center p-4">
                    <div class="h-20 flex items-center justify-center mb-3">
                        @if($product->primaryImage)
                            <img src="{{ Storage::url($product->primaryImage->file_path) }}" alt="{{ $product->name }}"
                                 class="max-h-20 w-auto object-contain group-hover:scale-105 transition-transform" loading="lazy">
                        @else
                            <span class="text-3xl">{{ $product->type === 'book' ? '📗' : '🫗' }}</span>
                        @endif
                    </div>
                    <p class="text-xs font-semibold text-gray-900 line-clamp-2 leading-snug group-hover:text-indigo-700 transition">{{ $product->name }}</p>
                    <p class="text-sm font-bold text-gray-800 mt-2">{{ number_format($product->selling_price, 2) }}</p>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Articles --}}
        @if($results['articles']->isNotEmpty())
        <section class="mb-14">
            <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                <span class="text-xl">📰</span>{{ __('app.search_pub.articles') }}
                <span class="text-sm font-normal text-gray-400">({{ $results['articles']->count() }})</span>
            </h2>
            <div class="space-y-4">
                @foreach($results['articles'] as $article)
                <a href="{{ route('blog.show', $article->slug) }}"
                   class="flex items-start gap-4 p-5 bg-white border border-gray-200 rounded-2xl hover:border-indigo-200 hover:shadow-sm transition group">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-indigo-50 shrink-0 flex items-center justify-center">
                        @if($article->coverImage)
                            <img src="{{ Storage::url($article->coverImage->file_path) }}" alt="" class="w-full h-full object-cover">
                        @else
                            <span class="text-2xl">📰</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 mb-1">
                            {{ optional($article->category)->name }} · {{ $article->published_at?->format('M d, Y') }}
                        </p>
                        <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-indigo-700 transition">{{ $article->getTranslatedTitle() }}</h3>
                        <p class="text-sm text-gray-500 line-clamp-2 mt-1 leading-relaxed">{{ $article->getTranslatedExcerpt() }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- FAQs --}}
        @if($results['faqs']->isNotEmpty())
        <section class="mb-14">
            <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                <span class="text-xl">❓</span>{{ __('app.search_pub.faqs') }}
                <span class="text-sm font-normal text-gray-400">({{ $results['faqs']->count() }})</span>
            </h2>
            <div class="space-y-3">
                @foreach($results['faqs'] as $faq)
                <div class="p-5 bg-white border border-gray-200 rounded-2xl hover:border-indigo-200 transition">
                    <h3 class="font-semibold text-gray-900 text-sm mb-2">{{ $faq->getTranslatedQuestion() }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed line-clamp-3">{{ $faq->getTranslatedAnswer() }}</p>
                    <a href="{{ route('faqs') }}" class="inline-block mt-3 text-xs text-indigo-600 font-semibold hover:underline">
                        See all FAQs →
                    </a>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        @endif
    </div>
</div>
</div>
