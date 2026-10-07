<div>

@php
    $svc          = app(\App\Services\SettingsService::class);
    $blogTitle    = $svc->get('blog_title',    $pageTitle);
    $blogSubtitle = $svc->get('blog_subtitle', 'News, guides, and updates from Al-Baraka Business Group');
@endphp

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
    <div class="absolute inset-0 opacity-10"
         style="background-image: radial-gradient(circle at 20% 50%, #6366f1 0%, transparent 55%), radial-gradient(circle at 80% 30%, #3b82f6 0%, transparent 50%);">
    </div>
    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <nav class="flex items-center gap-2 text-xs text-indigo-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-indigo-300">{{ $blogTitle }}</span>
        </nav>
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-500/20 border border-indigo-400/30
                         text-indigo-300 text-xs font-bold rounded-full uppercase tracking-widest mb-5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                Latest Articles
            </span>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-4 leading-tight">
                {{ $blogTitle }}
            </h1>
            <p class="text-indigo-200 text-lg leading-relaxed">{{ $blogSubtitle }}</p>
        </div>
    </div>
</div>

{{-- ── Main content ─────────────────────────────────────────────────────── --}}
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="flex flex-col lg:flex-row gap-10">

            {{-- ── Sidebar ──────────────────────────────────────────── --}}
            <aside class="lg:w-60 shrink-0 space-y-6">

                {{-- Search --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-5">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.350ms="search" type="text"
                               placeholder="Search articles..."
                               class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm
                                      focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition bg-gray-50">
                    </div>
                </div>

                {{-- Categories --}}
                @if($categories->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-2xl p-5">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">Categories</label>
                    <div class="space-y-1">
                        <button wire:click="$set('categoryId', '')"
                            class="w-full text-left px-3 py-2 rounded-xl text-sm transition font-medium
                                   {{ $categoryId === '' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">
                            All Categories
                        </button>
                        @foreach($categories as $cat)
                        <button wire:click="$set('categoryId', '{{ $cat->id }}')"
                            class="w-full text-left px-3 py-2 rounded-xl text-sm transition font-medium
                                   {{ $categoryId == $cat->id ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">
                            {{ $cat->name }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Type filter --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-5">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">Type</label>
                    <div class="space-y-1">
                        @foreach($articleTypes as $val => $label)
                        <button wire:click="$set('type', '{{ $val }}')"
                            class="w-full text-left px-3 py-2 rounded-xl text-sm transition font-medium
                                   {{ $type === $val ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </aside>

            {{-- ── Article grid ─────────────────────────────────────── --}}
            <div class="flex-1 min-w-0">

                {{-- Loading --}}
                <div wire:loading class="flex justify-center py-16">
                    <svg class="w-8 h-8 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div wire:loading.remove>
                    @if($articles->isEmpty())
                    <div class="flex flex-col items-center justify-center py-28 text-center">
                        <div class="w-20 h-20 mx-auto mb-5 bg-indigo-50 rounded-full flex items-center justify-center">
                            <svg class="w-9 h-9 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                        <h3 class="font-extrabold text-gray-800 text-lg mb-2">{{ __('app.cms.no_articles') }}</h3>
                        @if($search)
                        <p class="text-gray-500 text-sm mb-4">No results for "<strong>{{ $search }}</strong>"</p>
                        <button wire:click="$set('search', '')"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition">
                            Clear search
                        </button>
                        @else
                        <p class="text-gray-400 text-sm">No articles published yet. Check back soon.</p>
                        @endif
                    </div>
                    @else

                    {{-- Results header --}}
                    <div class="flex items-center justify-between mb-7">
                        <p class="text-sm text-gray-500">
                            <span class="font-bold text-gray-800">{{ $articles->total() }}</span>
                            {{ Str::plural('article', $articles->total()) }} found
                        </p>
                        @if($search || $categoryId || $type !== 'all')
                        <button wire:click="$set('search', ''); $set('categoryId', ''); $set('type', 'all')"
                                class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold transition">
                            Clear filters
                        </button>
                        @endif
                    </div>

                    {{-- Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($articles as $article)
                        <article class="group bg-white border border-gray-100 rounded-3xl overflow-hidden
                                        hover:shadow-xl hover:-translate-y-1 hover:border-indigo-200
                                        transition-all duration-300 flex flex-col">

                            {{-- Cover image --}}
                            <a href="{{ route('blog.show', $article->slug) }}"
                               class="block h-52 relative overflow-hidden bg-gradient-to-br from-indigo-50 to-blue-50 shrink-0">
                                @if($article->coverImage)
                                    <img src="{{ Storage::url($article->coverImage->file_path) }}"
                                         alt="{{ $article->coverImage->alt_text ?? $article->getTranslatedTitle() }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy">
                                @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                </div>
                                @endif

                                {{-- Category badge --}}
                                @if($article->category)
                                <span class="absolute top-3 left-3 px-2.5 py-1 bg-indigo-600 text-white
                                             text-[10px] font-bold rounded-lg uppercase tracking-wide shadow-sm">
                                    {{ $article->category->name }}
                                </span>
                                @endif

                                {{-- Type badge --}}
                                @if($article->article_type === 'guide')
                                <span class="absolute top-3 right-3 px-2.5 py-1 bg-amber-500 text-white
                                             text-[10px] font-bold rounded-lg uppercase tracking-wide shadow-sm">
                                    Guide
                                </span>
                                @endif
                            </a>

                            {{-- Content --}}
                            <div class="p-5 flex flex-col flex-1">
                                <div class="flex items-center gap-2 text-xs text-gray-400 mb-3">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ $article->published_at?->format('M d, Y') }}</span>
                                </div>

                                <h2 class="font-extrabold text-gray-900 leading-snug mb-3 line-clamp-2
                                           group-hover:text-indigo-700 transition text-base flex-1">
                                    <a href="{{ route('blog.show', $article->slug) }}">
                                        {{ $article->getTranslatedTitle() }}
                                    </a>
                                </h2>

                                <p class="text-gray-500 text-sm leading-relaxed line-clamp-2 mb-5">
                                    {{ $article->getTranslatedExcerpt() }}
                                </p>

                                <a href="{{ route('blog.show', $article->slug) }}"
                                   class="inline-flex items-center gap-1.5 text-sm font-bold text-indigo-600
                                          hover:text-indigo-700 transition mt-auto group/link">
                                    {{ __('app.cms.read_more') }}
                                    <svg class="w-4 h-4 group-hover/link:translate-x-0.5 transition-transform"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </article>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-12">{{ $articles->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</div>
