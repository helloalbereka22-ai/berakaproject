<div>

{{-- Header --}}
<div class="bg-gradient-to-br from-gray-900 to-indigo-950 text-white py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-extrabold mb-4">{{ __('app.nav_pub.faqs') }}</h1>
        <p class="text-indigo-300 text-lg">Find answers to your questions about our products and services.</p>
        {{-- Inline search --}}
        <div class="mt-8 relative max-w-xl mx-auto">
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="{{ __('app.search_pub.placeholder') }}"
                class="w-full ps-11 pe-4 py-3.5 bg-white/10 border border-white/20 rounded-2xl text-white placeholder-indigo-300 text-sm focus:outline-none focus:border-indigo-400 focus:bg-white/15 transition">
            <svg class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-indigo-300 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    {{-- Category filter pills --}}
    @if($categories->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-10">
        <button wire:click="$set('category', '')"
            class="px-4 py-2 rounded-full text-sm font-semibold transition
                   {{ $category === '' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ __('app.cms.all_categories') }}
        </button>
        @foreach($categories as $cat)
        <button wire:click="$set('category', '{{ $cat }}')"
            class="px-4 py-2 rounded-full text-sm font-semibold transition
                   {{ $category === $cat ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ $cat }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Loading --}}
    <div wire:loading class="text-center py-10">
        <svg class="w-8 h-8 animate-spin text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    <div wire:loading.remove>
        @if($grouped->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-bold text-gray-800 mb-1">{{ __('app.cms.no_faqs') }}</h3>
            @if($search)<button wire:click="$set('search', '')" class="mt-3 text-indigo-600 text-sm font-semibold hover:underline">Clear search</button>@endif
        </div>
        @else
            @foreach($grouped as $cat => $faqs)
            {{-- Group header --}}
            @if($cat)
            <h2 class="text-lg font-bold text-gray-900 mb-4 mt-10 first:mt-0 pb-2 border-b border-gray-200">{{ $cat }}</h2>
            @endif

            {{-- Accordion --}}
            <div class="space-y-3 mb-6" x-data="{ open: null }">
                @foreach($faqs as $i => $faq)
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-indigo-200 transition"
                     x-data="">
                    <button @click="open === {{ $faq->id }} ? open = null : open = {{ $faq->id }}"
                        class="w-full flex items-center justify-between gap-4 px-6 py-4 text-start"
                        :aria-expanded="open === {{ $faq->id }}">
                        <span class="font-semibold text-gray-900 text-sm leading-snug">{{ $faq->getTranslatedQuestion() }}</span>
                        <svg class="w-5 h-5 shrink-0 text-indigo-500 transition-transform duration-300"
                             :class="open === {{ $faq->id }} ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === {{ $faq->id }}" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="px-6 pb-5 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        {!! nl2br(e($faq->getTranslatedAnswer())) !!}
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        @endif
    </div>

    {{-- CTA --}}
    <div class="mt-16 bg-indigo-50 border border-indigo-100 rounded-2xl p-8 text-center">
        <h3 class="font-bold text-gray-900 mb-2">Still have questions?</h3>
        <p class="text-gray-600 text-sm mb-5">Our team is happy to help with any questions not covered here.</p>
        <a href="{{ route('contact') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition">
            {{ __('app.contact.title') }}
        </a>
    </div>
</div>
</div>
