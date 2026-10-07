<div class="bg-gray-50 min-h-screen">

    {{-- ── Breadcrumb header ── --}}
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="{{ route('home') }}" class="hover:text-indigo-600 transition font-medium">Home</a>
                <span class="text-gray-300">•</span>
                <span class="text-gray-800 font-semibold">News</span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- ── Search results notice ── --}}
        @if($search)
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Search results for: <strong class="text-gray-900">"{{ $search }}"</strong>
                ({{ $allNews->total() }} {{ Str::plural('article', $allNews->total()) }})
            </p>
            <button wire:click="$set('search','')"
                    class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">
                Clear search
            </button>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- ══ LEFT: Featured + More News ══════════════════════ --}}
            <div class="lg:col-span-2 space-y-8">

                @if(!$search && $featured)
                {{-- ── Featured / Latest News Card ── --}}
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-1 w-6 bg-indigo-600 rounded-full"></div>
                        <h2 class="text-lg font-extrabold text-gray-900">Latest News</h2>
                    </div>

                    @php $featImg = $featured->seo_metadata['news_image'] ?? null; @endphp

                    <a href="{{ route('news.show', $featured->slug) }}"
                       class="block group bg-white rounded-2xl overflow-hidden shadow-sm
                              hover:shadow-xl border border-gray-100 hover:border-indigo-200
                              transition-all duration-300">

                        {{-- Large image --}}
                        <div class="relative w-full aspect-[16/9] overflow-hidden bg-gradient-to-br from-indigo-50 to-blue-50">
                            @if($featImg)
                                <img src="{{ asset('storage/'.$featImg) }}"
                                     alt="{{ $featured->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                </div>
                            @endif
                            {{-- NEWS badge --}}
                            <span class="absolute top-4 right-4 px-3 py-1.5 bg-amber-500 text-white
                                         text-xs font-extrabold rounded-lg uppercase tracking-wide shadow-lg">
                                NEWS
                            </span>
                        </div>

                        {{-- Content --}}
                        <div class="p-6">
                            <h3 class="text-xl font-extrabold text-gray-900 leading-snug mb-3
                                       group-hover:text-indigo-700 transition">
                                {{ $featured->title }}
                            </h3>
                            @if($featured->excerpt)
                            <p class="text-gray-500 text-sm leading-relaxed mb-4 line-clamp-3">
                                {{ $featured->excerpt }}
                            </p>
                            @endif
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $featured->published_at?->format('d M Y') }}
                                    <span class="text-gray-300">·</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    {{ number_format($featured->view_count) }} views
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600
                                             text-white text-xs font-bold rounded-xl transition shadow-sm shadow-amber-200">
                                    READ MORE
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- ── More News / Search Results ── --}}
                @if($allNews->isNotEmpty())
                <div>
                    @if(!$search)
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-1 w-6 bg-gray-300 rounded-full"></div>
                        <h2 class="text-lg font-extrabold text-gray-900">More News</h2>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach($allNews as $article)
                        @php $img = $article->seo_metadata['news_image'] ?? null; @endphp
                        <a href="{{ route('news.show', $article->slug) }}"
                           class="group bg-white border border-gray-100 rounded-2xl overflow-hidden
                                  hover:shadow-lg hover:-translate-y-0.5 hover:border-indigo-200
                                  transition-all duration-200 flex flex-col">
                            {{-- Image --}}
                            <div class="relative h-44 overflow-hidden bg-gradient-to-br from-indigo-50 to-blue-50 shrink-0">
                                @if($img)
                                    <img src="{{ asset('storage/'.$img) }}" alt="{{ $article->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                    </div>
                                @endif
                                <span class="absolute top-3 right-3 px-2 py-0.5 bg-amber-500 text-white
                                             text-[10px] font-bold rounded-md uppercase tracking-wide">
                                    NEWS
                                </span>
                            </div>
                            {{-- Content --}}
                            <div class="p-4 flex flex-col flex-1">
                                <h3 class="font-extrabold text-gray-900 text-sm leading-snug mb-2
                                           group-hover:text-indigo-700 transition line-clamp-2">
                                    {{ $article->title }}
                                </h3>
                                @if($article->excerpt)
                                <p class="text-gray-500 text-xs leading-relaxed line-clamp-2 mb-3 flex-1">
                                    {{ $article->excerpt }}
                                </p>
                                @endif
                                <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-50">
                                    <span class="text-xs text-gray-400">{{ $article->published_at?->format('d M Y') }}</span>
                                    <span class="text-xs font-bold text-amber-600 group-hover:text-amber-700 transition">
                                        Read more →
                                    </span>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($allNews->hasPages())
                    <div class="mt-8">{{ $allNews->links() }}</div>
                    @endif
                </div>
                @elseif($search)
                <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center">
                    <div class="text-4xl mb-3">🔍</div>
                    <p class="font-bold text-gray-700">No news articles found</p>
                    <p class="text-gray-400 text-sm mt-1">Try different search terms</p>
                </div>
                @elseif(!$featured)
                <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center">
                    <div class="text-4xl mb-3">📰</div>
                    <p class="font-bold text-gray-700">No news published yet</p>
                    <p class="text-gray-400 text-sm mt-1">Check back soon for updates</p>
                </div>
                @endif
            </div>

            {{-- ══ RIGHT SIDEBAR ════════════════════════════════════ --}}
            <div class="space-y-6">

                {{-- Search --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-extrabold text-gray-900 text-sm mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Search News
                    </h3>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.400ms="search" type="text"
                               placeholder="Search news..."
                               class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm
                                      text-gray-700 placeholder-gray-400 focus:border-indigo-500
                                      focus:ring-2 focus:ring-indigo-500/20 outline-none transition bg-gray-50">
                    </div>
                </div>

                {{-- Recent News --}}
                @if($recent->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-extrabold text-gray-900 text-sm mb-5 flex items-center gap-2">
                        <div class="w-1 h-4 bg-amber-500 rounded-full"></div>
                        Recent News
                    </h3>
                    <div class="space-y-4">
                        @foreach($recent as $item)
                        @php $rImg = $item->seo_metadata['news_image'] ?? null; @endphp
                        <a href="{{ route('news.show', $item->slug) }}"
                           class="flex items-start gap-3 group hover:bg-gray-50 rounded-xl p-2 -mx-2 transition">
                            {{-- Thumbnail --}}
                            <div class="w-16 h-14 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                                @if($rImg)
                                    <img src="{{ asset('storage/'.$rImg) }}" alt="{{ $item->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-indigo-50">
                                        <svg class="w-5 h-5 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                    </div>
                                @endif
                            </div>
                            {{-- Text --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-800 leading-snug line-clamp-2
                                          group-hover:text-indigo-700 transition">
                                    {{ $item->title }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $item->published_at?->format('d M Y') }}
                                </p>
                            </div>
                        </a>
                        @if(!$loop->last)
                        <div class="border-t border-gray-100"></div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
