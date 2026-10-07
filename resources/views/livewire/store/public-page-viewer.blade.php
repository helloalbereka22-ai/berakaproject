<div>

{{-- Breadcrumb --}}
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-xs text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Home</a>
            <span>/</span>
            <span class="text-gray-700">{{ $page->getTranslatedTitle() }}</span>
        </nav>
    </div>
</div>

{{-- Page header --}}
<div class="bg-gradient-to-br from-gray-900 to-indigo-950 text-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl sm:text-4xl font-extrabold">{{ $page->getTranslatedTitle() }}</h1>
        @if($page->published_at)
        <p class="text-indigo-300 text-sm mt-3">Last updated {{ $page->published_at->format('F d, Y') }}</p>
        @endif
    </div>
</div>

{{-- Content --}}
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
    <div class="prose prose-gray prose-lg max-w-none
                prose-headings:font-bold prose-headings:text-gray-900
                prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
                prose-img:rounded-xl">
        @if($page->getTranslatedBody())
            {!! $page->getTranslatedBody() !!}
        @else
            <p class="text-gray-500 italic">This page has no content yet.</p>
        @endif
    </div>

    <div class="mt-10 pt-6 border-t border-gray-100">
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition">
            <svg class="w-4 h-4 rtl-flip" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Home
        </a>
    </div>
</div>

</div>
