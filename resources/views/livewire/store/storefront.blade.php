<div>

{{-- ══ HERO - PROMO SLIDER ══════════════════════════════════════════════════ --}}
@php
    // Flush cache
    \Illuminate\Support\Facades\Cache::forget('platform_settings');

    // Load promo images — check all settings rows (with or without business_id)
    $slides = collect(range(1,5))
        ->map(fn($i) => \DB::table('settings')
            ->where('key', "promo_image_{$i}")
            ->orderByRaw('business_id IS NOT NULL')
            ->value('value'))
        ->filter()
        ->values();

    // Last resort: scan storage/promo folder directly
    if ($slides->isEmpty()) {
        $dir = storage_path('app/public/promo');
        if (is_dir($dir)) {
            $slides = collect(array_diff(scandir($dir), ['.','..']))
                ->filter(fn($f) => preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f))
                ->sort()
                ->map(fn($f) => 'promo/' . $f)
                ->values();
        }
    }
@endphp

@if($slides->isNotEmpty())
{{-- Auto-sliding Promo Carousel --}}
<section x-data="{
    current: 0,
    total: {{ $slides->count() }},
    start() {
        setInterval(() => { this.current = (this.current + 1) % this.total; }, 4000);
    },
    prev() { this.current = (this.current - 1 + this.total) % this.total; },
    next() { this.current = (this.current + 1) % this.total; }
}" x-init="start()" class="relative overflow-hidden bg-black" style="height:500px;">

    @foreach($slides as $i => $path)
    <div x-show="current === {{ $i }}"
         x-transition:enter="transition ease-in-out duration-700"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in-out duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0">
        <img src="{{ asset('storage/' . $path) }}"
             alt="Promo {{ $i + 1 }}"
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
    </div>
    @endforeach

    {{-- Arrows --}}
    <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-black/40 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-black/40 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>

    {{-- Dots --}}
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex gap-2">
        @foreach($slides as $i => $path)
        <button @click="current = {{ $i }}"
            :class="current === {{ $i }} ? 'w-6 bg-white' : 'w-2 bg-white/50'"
            class="h-2 rounded-full transition-all duration-300"></button>
        @endforeach
    </div>

    {{-- Hero text overlay from settings --}}
    @php
        $svc2          = app(\App\Services\SettingsService::class);
        $heroTitleSlide    = $svc2->get('home_hero_title',    '');
        $heroSubtitleSlide = $svc2->get('home_hero_subtitle', '');
    @endphp
    @if($heroTitleSlide || $heroSubtitleSlide)
    <div class="absolute inset-0 z-10 flex flex-col items-center justify-center text-center px-4 pointer-events-none">
        @if($heroTitleSlide)
        <h1 class="text-3xl sm:text-5xl font-black text-white drop-shadow-2xl mb-3">{{ $heroTitleSlide }}</h1>
        @endif
        @if($heroSubtitleSlide)
        <p class="text-white/90 text-lg max-w-2xl drop-shadow-lg">{{ $heroSubtitleSlide }}</p>
        @endif
    </div>
    @endif
</section>

@else
{{-- No images yet — show GM-configured hero or simple fallback --}}
@php
    $svc           = app(\App\Services\SettingsService::class);
    $heroTitle     = $svc->get('home_hero_title',    'Welcome to Al-Baraka Store');
    $heroSubtitle  = $svc->get('home_hero_subtitle', '');
@endphp
<section class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white" style="height:300px;">
    <div class="h-full flex flex-col items-center justify-center text-center px-4 gap-5">
        <h1 class="text-3xl sm:text-5xl font-black text-white">{{ $heroTitle }}</h1>
        @if($heroSubtitle)
        <p class="text-indigo-200 text-lg max-w-2xl leading-relaxed">{{ $heroSubtitle }}</p>
        @endif
        <div class="flex gap-3">
            <a href="{{ route('store.products') }}" class="px-7 py-3 bg-white text-indigo-900 font-black rounded-xl text-sm">🛍️ Shop Products</a>
            <a href="{{ route('manufacturing') }}" class="px-7 py-3 bg-white/20 border border-white/40 text-white font-bold rounded-xl text-sm">🏭 Manufacturing</a>
        </div>
    </div>
</section>
@endif

{{-- ══ ORGANIZATIONS OVERVIEW ═══════════════════════════════════════════════ --}}
<section class="py-12 sm:py-16 md:py-24 lg:py-28 bg-gradient-to-br from-slate-50 via-white to-slate-50">
    <div class="w-full px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8 sm:mb-12 md:mb-16">
                <span class="inline-block px-3 sm:px-4 py-1.5 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold uppercase rounded-full mb-3 sm:mb-4">Our Portfolio</span>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-gray-900 mb-2 sm:mb-4">{{ __('app.organizations.title') }}</h2>
                <p class="text-sm sm:text-base md:text-lg text-gray-600 max-w-3xl mx-auto px-2">{{ __('app.organizations.subtitle') }}</p>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 mt-8 sm:mt-12 md:mt-16">
                {{-- Oil Factory --}}
                <div class="bg-white rounded-xl sm:rounded-2xl border border-gray-200 hover:border-amber-400 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                    <div class="h-1 sm:h-1.5 bg-gradient-to-r from-amber-400 to-orange-500"></div>
                    <div class="p-6 sm:p-8 md:p-10">
                        <h3 class="text-lg sm:text-2xl md:text-3xl font-bold text-gray-900 mb-3 flex items-center gap-2 sm:gap-3">
                            <svg class="w-6 sm:w-8 h-6 sm:h-8 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5zm0-5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm7.43-2.53c.04-.32.07-.64.07-.97 0-.32-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.31-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65c-.04-.24-.25-.41-.5-.41h-4c-.25 0-.46.17-.49.41l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.66-.07.98 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.03.24.24.41.5.41h4c.25 0 .46-.17.49-.41l.37-2.65c.63-.25 1.17-.59 1.69-.99l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/>
                            </svg>
                            <span class="line-clamp-2">{{ __('app.organizations.oil_name') }}</span>
                        </h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed mb-6">{{ __('app.organizations.oil_desc') }}</p>
                        
                        <div class="mb-6 pb-6 border-b border-gray-100">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-3">Capabilities</p>
                            <div class="space-y-2">
                                @foreach(['Natural Ingredients', 'Quality Certified', 'Multiple Sizes'] as $tag)
                                <div class="flex items-center gap-2 text-xs sm:text-sm">
                                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-gray-800 font-semibold">{{ $tag }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <a href="{{ route('manufacturing') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-sm sm:text-base font-bold rounded-lg transition-all">
                            View Manufacturing
                        </a>
                    </div>
                </div>

                {{-- Bookstore --}}
                <div class="bg-white rounded-xl sm:rounded-2xl border border-gray-200 hover:border-emerald-400 hover:shadow-2xl transition-all duration-300 overflow-hidden">
                    <div class="h-1 sm:h-1.5 bg-gradient-to-r from-emerald-400 to-teal-500"></div>
                    <div class="p-6 sm:p-8 md:p-10">
                        <h3 class="text-lg sm:text-2xl md:text-3xl font-bold text-gray-900 mb-3 flex items-center gap-2 sm:gap-3">
                            <svg class="w-6 sm:w-8 h-6 sm:h-8 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 18H4V4h16v16zm-5.5-8c.83 0 1.5-.67 1.5-1.5S15.33 9 14.5 9 13 9.67 13 10.5s.67 1.5 1.5 1.5zm0 6c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z"/>
                            </svg>
                            <span class="line-clamp-2">{{ __('app.organizations.bookstore_name') }}</span>
                        </h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed mb-6">{{ __('app.organizations.bookstore_desc') }}</p>
                        
                        <div class="mb-6 pb-6 border-b border-gray-100">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-3">Capabilities</p>
                            <div class="space-y-2">
                                @foreach(['Multi-Branch', 'Wide Selection', 'All Genres'] as $tag)
                                <div class="flex items-center gap-2 text-xs sm:text-sm">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-gray-800 font-semibold">{{ $tag }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <a href="{{ route('store.products', ['type' => 'book']) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm sm:text-base font-bold rounded-lg transition-all">
                            View Products
                        </a>
                    </div>
                </div>
            </div>

            <!-- Trust Section - HIDDEN -->
            {{-- <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 md:gap-8 mt-12 sm:mt-16 md:mt-24 pt-8 sm:pt-12 md:pt-16 border-t border-gray-200">
                <div class="text-center">
                    <div class="flex justify-center mb-2 sm:mb-3"><svg class="w-5 sm:w-7 h-5 sm:h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></div>
                    <p class="text-base sm:text-lg font-bold text-gray-900">Pure Raw</p>
                    <p class="text-xs text-gray-600">Trusted sources</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-2 sm:mb-3"><svg class="w-5 sm:w-7 h-5 sm:h-7 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></div>
                    <p class="text-base sm:text-lg font-bold text-gray-900">Quality</p>
                    <p class="text-xs text-gray-600">QC tested</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-2 sm:mb-3"><svg class="w-5 sm:w-7 h-5 sm:h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8 4m-8-4v10M8 7v10m8-10v10"/></svg></div>
                    <p class="text-base sm:text-lg font-bold text-gray-900">Packages</p>
                    <p class="text-xs text-gray-600">500ml-5L</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-2 sm:mb-3"><svg class="w-5 sm:w-7 h-5 sm:h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <p class="text-base sm:text-lg font-bold text-gray-900">Trusted</p>
                    <p class="text-xs text-gray-600">Individuals & B2B</p>
                </div>
            </div> --}}
        </div>
    </div>
</section>

{{-- ══ FEATURED PRODUCTS ════════════════════════════════════════════════════ --}}
@if($featured->isNotEmpty())
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">{{ __('app.product.featured') }}</h2>
                <p class="text-gray-500 mt-2">Hand-picked quality products</p>
            </div>
            <a href="{{ route('store.products') }}"
               class="hidden sm:flex items-center gap-2 text-indigo-600 font-semibold text-sm hover:text-indigo-700 transition">
                {{ __('app.product.view_all') }}
                <svg class="w-4 h-4 rtl-flip" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($featured as $product)
                @include('livewire.store.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="text-center mt-10 sm:hidden">
            <a href="{{ route('store.products') }}"
               class="inline-flex items-center gap-2 px-6 py-3 border border-indigo-200 text-indigo-600 font-semibold text-sm rounded-xl hover:bg-indigo-50 transition">
                {{ __('app.product.view_all') }}
            </a>
        </div>
    </div>
</section>
@endif

{{-- ══ VALUE PROPOSITIONS ═══════════════════════════════════════════════════ --}}
<section class="py-16 bg-white border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            @foreach([
                ['icon' => '🌿', 'title' => 'Natural Ingredients',    'desc' => 'Pure raw materials from trusted sources'],
                ['icon' => '✅', 'title' => 'Quality Guaranteed',      'desc' => 'Every batch passes strict QC inspection'],
                ['icon' => '📦', 'title' => 'Multiple Package Sizes',  'desc' => '500ml, 1L, 5L and more available'],
                ['icon' => '🤝', 'title' => 'Trusted by Customers',    'desc' => 'Serving individuals and businesses'],
            ] as $prop)
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 flex items-center justify-center text-2xl mb-4 border border-indigo-100">{{ $prop['icon'] }}</div>
                <h3 class="font-bold text-gray-900 text-sm mb-1">{{ $prop['title'] }}</h3>
                <p class="text-gray-500 text-xs leading-relaxed">{{ $prop['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ EMPTY STATE ══════════════════════════════════════════════════════════ --}}
@if($featured->isEmpty() && $bestSellers->isEmpty() && $newArrivals->isEmpty())
<section class="py-32 text-center">
    <div class="max-w-md mx-auto px-4">
        <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Products Coming Soon</h2>
        <p class="text-gray-500 text-sm">Our catalog is being set up. Check back soon.</p>
    </div>
</section>
@endif

</div>
