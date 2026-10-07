<div>

{{-- Breadcrumb --}}
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-xs text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Home</a>
            <span>/</span>
            <a href="{{ route('blog') }}" class="hover:text-indigo-600 transition">{{ __('app.nav_pub.blog') }}</a>
            @if($article->category)
            <span>/</span>
            <span class="text-gray-700">{{ $article->category->name }}</span>
            @endif
        </nav>
    </div>
</div>

<article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Category + type badge --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        @if($article->category)
        <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full">{{ $article->category->name }}</span>
        @endif
        @if($article->article_type === 'guide')
        <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">Product Guide</span>
        @endif
    </div>

    {{-- Title --}}
    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-5"
        dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
        {{ $article->getTranslatedTitle() }}
    </h1>

    {{-- Meta --}}
    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-8 pb-8 border-b border-gray-200">
        @if($article->author)
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700">
                {{ strtoupper(substr($article->author->name, 0, 1)) }}
            </div>
            <span>{{ $article->author->name }}</span>
        </div>
        @endif
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            {{ __('app.cms.published_on') }} {{ $article->published_at?->format('F d, Y') }}
        </span>
        @if($article->view_count > 0)
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            {{ number_format($article->view_count) }} views
        </span>
        @endif
    </div>

    {{-- Cover image --}}
    @if($article->coverImage)
    <div class="rounded-2xl overflow-hidden mb-10 aspect-video bg-gray-100">
        <img src="{{ Storage::url($article->coverImage->file_path) }}"
             alt="{{ $article->coverImage->alt_text ?? $article->title }}"
             class="w-full h-full object-cover">
    </div>
    @endif

    {{-- Body --}}
    <div class="prose prose-gray prose-lg max-w-none
                prose-headings:font-bold prose-headings:text-gray-900
                prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
                prose-img:rounded-xl prose-img:shadow-sm"
         dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
         style="{{ $isRtl ? 'text-align: right; font-family: inherit;' : '' }}">
        {!! $article->getTranslatedBody() !!}
    </div>

    {{-- Tags --}}
    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-2 mt-10 pt-8 border-t border-gray-100">
        @foreach($article->tags as $tag)
        <span class="px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full hover:bg-indigo-100 hover:text-indigo-700 transition cursor-default">
            #{{ $tag->name }}
        </span>
        @endforeach
    </div>
    @endif

    {{-- Back link --}}
    <div class="mt-10">
        <a href="{{ route('blog') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition">
            <svg class="w-4 h-4 rtl-flip" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ __('app.nav_pub.blog') }}
        </a>
    </div>
</article>

{{-- Related articles --}}
@if($related->isNotEmpty())
<section class="bg-gray-50 py-16 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 mb-8">Related Articles</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            @foreach($related as $rel)
            <article class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md hover:border-indigo-200 transition group">
                <div class="h-36 bg-gradient-to-br from-indigo-50 to-blue-50 overflow-hidden">
                    @if($rel->coverImage)
                        <img src="{{ Storage::url($rel->coverImage->file_path) }}"
                             alt="{{ $rel->coverImage->alt_text ?? $rel->title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                             loading="lazy">
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-400 mb-2">{{ $rel->published_at?->format('M d, Y') }}</p>
                    <h3 class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-700 transition mb-3">
                        <a href="{{ route('blog.show', $rel->slug) }}">{{ $rel->getTranslatedTitle() }}</a>
                    </h3>
                    <a href="{{ route('blog.show', $rel->slug) }}"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition">
                        {{ __('app.cms.read_more') }} →
                    </a>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif
</div>
