<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ (Session::get('is_rtl') || request()->cookie('is_rtl')) ? 'rtl' : 'ltr' }}"
      class="h-full scroll-smooth"
      style="visibility:hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    
    {{-- SEO Meta Tags (managed by SeoService) --}}
    @php
        $seoService = app(\App\Services\SeoService::class);
        $metaTags = $seoService->getMetaTags(
            $title ?? 'Al-Baraka Business Group',
            $description ?? __('app.hero.description'),
            url()->current()
        );
    @endphp
    
    <title>{{ $metaTags['title'] }}</title>
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta name="keywords" content="{{ $metaTags['keywords'] }}">
    
    {{-- Open Graph Tags --}}
    <meta property="og:title"       content="{{ $metaTags['og']['title'] }}">
    <meta property="og:description" content="{{ $metaTags['og']['description'] }}">
    <meta property="og:type"        content="{{ $metaTags['og']['type'] }}">
    <meta property="og:url"         content="{{ $metaTags['og']['url'] }}">
    <meta property="og:image"       content="{{ $metaTags['og']['image'] }}">
    <meta property="og:site_name"   content="{{ $metaTags['og']['site_name'] }}">
    
    {{-- Twitter Card Tags --}}
    <meta name="twitter:card"       content="{{ $metaTags['twitter']['card'] }}">
    <meta name="twitter:title"      content="{{ $metaTags['twitter']['title'] }}">
    <meta name="twitter:description" content="{{ $metaTags['twitter']['description'] }}">
    <meta name="twitter:image"      content="{{ $metaTags['twitter']['image'] }}">
    
    {{-- Robots & Canonical --}}
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">

    <!-- Favicon with system logo and cache busting -->
    @php
        $systemLogo = app(\App\Services\SettingsService::class)->get('system_logo_path', '');
        $faviconUrl = $systemLogo ? asset('storage/' . $systemLogo) : asset('favicon.svg');
    @endphp
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}?v={{ time() }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}?v={{ time() }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Al-Baraka">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Font Awesome CDN for brand icons (social media) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVJkEZSMUkrQ6usKu8zIvxXnMqt1dZWH7WBrJ6RTW3K/gjjsuG+t63kztHQfsb2+0nYDjicaSrg==" crossorigin="anonymous" referrerpolicy="no-referrer">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                        colors: {
                            brand: { 50:'#f0f4ff', 100:'#e0eaff', 500:'#4f6ef7', 600:'#3b5cf6', 700:'#2d4ed8', 900:'#1e3a8a' },
                            gold:  { 400:'#f59e0b', 500:'#d97706', 600:'#b45309' },
                        }
                    }
                }
            }
        </script>
        <script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
    @endif
    @livewireStyles
    <style>
        body            { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak]       { display: none !important; }
        /* Hide body until Tailwind loads to prevent unstyled flash */
        body.loading    { visibility: hidden; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        /* Smooth dropdown */
        .nav-dropdown   { transition: opacity .15s ease, transform .15s ease; }
        /* RTL-aware icon flip */
        [dir="rtl"] .rtl-flip { transform: scaleX(-1); }
    </style>
    <script>
        // Add loading class immediately to hide unstyled content
        document.documentElement.classList.add('loading');
    </script>

    {{-- JSON-LD Structured Data for SEO --}}
    <script type="application/ld+json">
    {!! json_encode($seoService->getOrganizationSchema()) !!}
    </script>
    
    <script type="application/ld+json">
    {!! json_encode($seoService->getLocalBusinessSchema()) !!}
    </script>
</head>
<body class="bg-white text-gray-900 min-h-screen flex flex-col antialiased"
      x-data="{ mobileMenuOpen: false, cartOpen: {{ (isset($errors) && $errors->any()) ? 'true' : 'false' }} }"
      x-init="
        if(sessionStorage.getItem('cartWasOpen')==='1'){
            cartOpen=true;
            sessionStorage.removeItem('cartWasOpen');
        }
      ">

{{-- ══════════════════════════════════════════════════════════════════════
     MAIN NAVIGATION - PROFESSIONAL
═══════════════════════════════════════════════════════════════════════ --}}
<header class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-sm" id="main-nav">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-8">

            {{-- ── Logo + Brand ─────────────────────────────────────────────── --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0 group">
                @php use Illuminate\Support\Facades\Storage; @endphp
                @php
                    $logoPath = app(\App\Services\SettingsService::class)->get('system_logo_path', '');
                @endphp
                @if($logoPath && Storage::disk('public')->exists($logoPath))
                    <img src="{{ asset('storage/' . $logoPath) }}" 
                         alt="Al-Baraka Logo"
                         class="h-11 w-11 rounded-xl object-cover shadow-md group-hover:shadow-lg transition-shadow duration-300">
                @else
                    <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center shadow-md group-hover:shadow-lg transition-shadow duration-300">
                        <span class="text-white font-bold text-lg">A</span>
                    </div>
                @endif
                <div class="hidden sm:block">
                    <p class="text-lg font-bold text-gray-900 tracking-tight">Al-Baraka</p>
                    <p class="text-xs font-semibold text-gray-600">Business Group</p>
                </div>
            </a>

            {{-- ── Desktop Nav (7 items including Shopping Cart) ──────────────────────────────────────── --}}
            <nav class="hidden lg:flex items-center gap-1 flex-1" aria-label="Main navigation">
                @php
                    $navLinks = [
                        ['route' => 'home',          'label' => __('app.nav_pub.home')],
                        ['route' => 'store.products','label' => __('app.nav_pub.products')],
                        ['route' => 'manufacturing', 'label' => __('app.nav_pub.manufacturing')],
                    ];
                @endphp
                @foreach($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                   class="px-4 py-2 text-base font-semibold transition-all duration-200 relative group
                          {{ request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*')
                              ? 'text-slate-900'
                              : 'text-gray-700 hover:text-slate-900' }}">
                    {{ $link['label'] }}
                    <span class="absolute inset-x-0 bottom-1 h-0.5 bg-slate-900 transform scale-x-0 group-hover:scale-x-100 {{ request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*') ? 'scale-x-100' : '' }} transition-transform duration-300 origin-left"></span>
                </a>
                @endforeach

                {{-- Shopping Cart (between Manufacturing and News) --}}
                @php $cartItems = session('guest_cart', []); $cartCount = count($cartItems); @endphp
                <button @click="cartOpen = true"
                   class="px-4 py-2 text-base font-semibold text-gray-700 hover:text-slate-900 relative transition-colors duration-200 group flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-700 group-hover:text-slate-900 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="hidden md:inline">{{ __('app.cart.title') }}</span>
                    @if($cartCount > 0)
                    <span id="cart-count-badge" class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">
                        {{ $cartCount }}
                    </span>
                    @endif
                </button>

                {{-- Remaining nav items (News, About, Contact) --}}
                @php
                    $remainingLinks = [
                        ['route' => 'news',          'label' => __('app.nav_pub.news')],
                        ['route' => 'about',         'label' => __('app.nav_pub.about')],
                        ['route' => 'contact',       'label' => __('app.nav_pub.contact')],
                    ];
                @endphp
                @foreach($remainingLinks as $link)
                <a href="{{ route($link['route']) }}"
                   class="px-4 py-2 text-base font-semibold transition-all duration-200 relative group
                          {{ request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*')
                              ? 'text-slate-900'
                              : 'text-gray-700 hover:text-slate-900' }}">
                    {{ $link['label'] }}
                    <span class="absolute inset-x-0 bottom-1 h-0.5 bg-slate-900 transform scale-x-0 group-hover:scale-x-100 {{ request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*') ? 'scale-x-100' : '' }} transition-transform duration-300 origin-left"></span>
                </a>
                @endforeach
            </nav>

            {{-- ── Right section (Language, Login, Mobile menu) ───────────────────────────────────── --}}
            <div class="flex items-center gap-4 shrink-0">

                {{-- Language switcher --}}
                <div class="hidden md:block">
                    @livewire('public.language-switcher')
                </div>

                {{-- Login Button --}}
                <a href="{{ route('login') }}"
                   class="hidden sm:flex items-center gap-2 px-5 py-2.5 text-base font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('app.nav.sign_in') }}
                </a>

                {{-- Mobile menu toggle --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden p-2.5 text-gray-700 hover:text-slate-900 hover:bg-gray-100 transition-all rounded-lg"
                    aria-label="Toggle menu">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Mobile drawer ───────────────────────────────────────── --}}
        <div x-show="mobileMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden border-t border-gray-200 py-4 space-y-1 bg-gray-50">

            @php
                $allMobileLinks = [
                    ['route' => 'home',          'label' => __('app.nav_pub.home')],
                    ['route' => 'store.products','label' => __('app.nav_pub.products')],
                    ['route' => 'manufacturing', 'label' => __('app.nav_pub.manufacturing')],
                    ['route' => 'news',          'label' => __('app.nav_pub.news')],
                    ['route' => 'about',         'label' => __('app.nav_pub.about')],
                    ['route' => 'contact',       'label' => __('app.nav_pub.contact')],
                ];
            @endphp

            @foreach($allMobileLinks as $key => $link)
                @if($key === 3)
                {{-- Shopping Cart button before News --}}
                @php $cartItems = session('guest_cart', []); $cartCount = count($cartItems); @endphp
                <button @click="cartOpen = true; mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-base font-semibold transition-all w-full text-gray-700 hover:bg-white hover:text-slate-900">
                    <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>{{ __('app.cart.title') }}</span>
                    @if($cartCount > 0)
                    <span class="text-xs font-bold bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                </button>
                @endif

            <a href="{{ route($link['route']) }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg text-base font-semibold transition-all
                      {{ request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*')
                          ? 'text-slate-900 bg-white shadow-sm'
                          : 'text-gray-700 hover:bg-white hover:text-slate-900' }}">
                {{ $link['label'] }}
            </a>
            @endforeach

            <div class="border-t border-gray-200 mt-4 pt-4 space-y-3 px-4">
                {{-- Mobile language switcher --}}
                <div>
                    @livewire('public.language-switcher')
                </div>
                
                <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-slate-900 text-white text-base font-semibold rounded-lg hover:bg-slate-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('app.nav.sign_in') }}
                </a>
            </div>
        </div>
    </div>
</header>

{{-- ══════════════════════════════════════════════════════════════════════
     Flash messages
═══════════════════════════════════════════════════════════════════════ --}}
@if(session('success'))
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4" role="alert">
    <div class="flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        {{ session('success') }}
    </div>
</div>
@endif
@if(session('error'))
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4" role="alert">
    <div class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-medium">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        {{ session('error') }}
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════════════════════════════════ --}}
<main class="flex-1">
    {{ $slot }}
</main>

{{-- ══════════════════════════════════════════════════════════════════════
     FOOTER — all content from SettingsService (admin-managed)
═══════════════════════════════════════════════════════════════════════ --}}
@php
    /** @var \App\Services\SettingsService $settings */
    $settings      = app(\App\Services\SettingsService::class);
    $orgName       = $settings->get('org_name',        'Al-Baraka Business Group');
    $orgTagline    = $settings->get('org_tagline',     'Premium Oils & Curated Books');
    $orgDesc       = $settings->get('org_description', '');
    $orgLogo       = $settings->get('org_logo_path',   '');
    $cPhone        = $settings->get('contact_phone',   '');
    $cPhoneSec     = $settings->get('contact_phone_secondary', '');
    $cEmail        = $settings->get('contact_email',   '');
    $cAddress      = $settings->get('contact_address', '');
    $cHours        = $settings->get('contact_office_hours', '');
    $cMaps         = $settings->get('contact_maps_url', '');
    $socialLinks   = $settings->activeSocialLinks();

    $socialMeta = [
        'social_facebook'  => ['label' => 'Facebook',  'icon' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
        'social_telegram'  => ['label' => 'Telegram',  'icon' => 'M21.543 7.104c.15-.978-.073-1.487-.321-1.727-.247-.241-.688-.34-1.19-.24a49.77 49.77 0 00-3.093.924 145.85 145.85 0 00-4.346 1.498 213.69 213.69 0 00-3.784 1.418c-1.047.407-1.862.739-2.433.999l-.02.009-.008.003-.003.002h-.001c-.376.173-.635.357-.795.57-.16.213-.2.44-.167.624.033.184.138.376.329.537.192.162.475.297.837.397l3.01.878c.127.448.52 1.81.854 2.919.155.507.305.994.415 1.344.056.175.108.324.152.44.044.115.108.26.213.367.245.252.59.334.927.27l1.858-.384L15 22l1.543-1.473 3.228-2.784a1 1 0 00.293-.72 1 1 0 00-.33-.702l-2.05-1.83 2.88-3.093.002-.003A3.56 3.56 0 0021.543 7.104z'],
        'social_youtube'   => ['label' => 'YouTube',   'icon' => 'M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.41 19.1C5.12 19.56 12 19.56 12 19.56s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z M9.75 15.02l5.75-3.27-5.75-3.27v6.54z'],
        'social_linkedin'  => ['label' => 'LinkedIn',  'icon' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z'],
        'social_instagram' => ['label' => 'Instagram', 'icon' => 'M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zm1.5-4.87h.01M6.5 19.5h11a3 3 0 003-3v-11a3 3 0 00-3-3h-11a3 3 0 00-3 3v11a3 3 0 003 3z'],
        'social_twitter'   => ['label' => 'X / Twitter','icon' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
        'social_whatsapp'  => ['label' => 'WhatsApp',  'icon' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'],
        'social_tiktok'    => ['label' => 'TikTok',    'icon' => 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.16 8.16 0 004.77 1.52V6.72a4.85 4.85 0 01-1-.03z'],
    ];
@endphp
<footer class="bg-gray-950 text-gray-400 mt-20" aria-label="Site footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">

        {{-- ── Main footer grid ──────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

            {{-- Col 1: Brand + description + social --}}
            <div class="sm:col-span-2 lg:col-span-1">
                {{-- Logo / name --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-4 group">
                    @if($orgLogo)
                        <img src="{{ Storage::url($orgLogo) }}"
                             alt="{{ $orgName }}"
                             class="h-10 w-auto object-contain rounded-lg bg-white p-0.5 shrink-0">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-700 to-indigo-600 flex items-center justify-center shadow shrink-0">
                            <span class="text-white font-extrabold text-base">A</span>
                        </div>
                    @endif
                    <div>
                        <p class="text-white font-extrabold text-base leading-tight group-hover:text-indigo-300 transition">{{ $orgName }}</p>
                        @if($orgTagline)
                        <p class="text-indigo-400 text-[10px] font-semibold uppercase tracking-widest leading-tight">{{ $orgTagline }}</p>
                        @endif
                    </div>
                </a>
                @if($orgDesc)
                <p class="text-sm leading-relaxed text-gray-500 mb-6">{{ $orgDesc }}</p>
                @endif

                {{-- Social icons — only rendered when URL is valid --}}
                @if(!empty($socialLinks))
                <div class="flex flex-wrap gap-2">
                    @foreach($socialLinks as $key => $url)
                    @if(isset($socialMeta[$key]))
                    <a href="{{ $url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="{{ $socialMeta[$key]['label'] }}"
                       class="w-9 h-9 rounded-xl bg-gray-800 hover:bg-indigo-600 flex items-center justify-center text-gray-400 hover:text-white transition-all duration-200 group"
                       title="{{ $socialMeta[$key]['label'] }}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="{{ $socialMeta[$key]['icon'] }}"/>
                        </svg>
                    </a>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Col 2: Quick links --}}
            <div>
                <h3 class="text-white text-xs font-bold uppercase tracking-wider mb-5">Quick Links</h3>
                <ul class="space-y-3 text-sm">
                    @foreach([
                        ['route' => 'home',          'label' => __('app.nav_pub.home')],
                        ['route' => 'store.products','label' => __('app.nav_pub.products')],
                        ['route' => 'manufacturing', 'label' => __('app.nav_pub.manufacturing')],
                        ['route' => 'news',          'label' => __('app.nav_pub.news')],
                        ['route' => 'about',         'label' => __('app.nav_pub.about')],
                        ['route' => 'contact',       'label' => __('app.nav_pub.contact')],
                        ['route' => 'store.cart',    'label' => __('app.cart.title')],
                    ] as $link)
                    <li>
                        <a href="{{ route($link['route']) }}"
                           class="hover:text-white hover:translate-x-0.5 transition-all duration-150 flex items-center gap-1.5 group">
                            <svg class="w-3 h-3 text-gray-700 group-hover:text-indigo-400 transition rtl-flip shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            {{ $link['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Col 3: Shop links (HIDDEN) --}}
            {{-- Hidden for cleaner footer --}}

            {{-- Col 4: Contact info (admin-managed) --}}
            <div>
                <h3 class="text-white text-xs font-bold uppercase tracking-wider mb-5">{{ __('app.contact.title') }}</h3>

                @if(!$cPhone && !$cEmail && !$cAddress && !$cHours)
                <p class="text-gray-600 text-sm italic">{{ __('app.contact.no_info') }}</p>
                @else
                <address class="not-italic space-y-4 text-sm">

                    {{-- Phone --}}
                    @if($cPhone)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <a href="tel:{{ preg_replace('/\s+/', '', $cPhone) }}"
                               class="text-gray-300 hover:text-white transition block leading-snug">{{ $cPhone }}</a>
                            @if($cPhoneSec = $settings->get('contact_phone_secondary',''))
                            <a href="tel:{{ preg_replace('/\s+/', '', $cPhoneSec) }}"
                               class="text-gray-500 hover:text-gray-300 transition block text-xs mt-0.5">{{ $cPhoneSec }}</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Email --}}
                    @if($cEmail)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <a href="mailto:{{ $cEmail }}"
                               class="text-gray-300 hover:text-white transition block leading-snug break-all">{{ $cEmail }}</a>
                            @if($cEmailSec = $settings->get('contact_email_secondary',''))
                            <a href="mailto:{{ $cEmailSec }}"
                               class="text-gray-500 hover:text-gray-300 transition block text-xs mt-0.5 break-all">{{ $cEmailSec }}</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Address --}}
                    @if($cAddress)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            @if($cMaps)
                            <a href="{{ $cMaps }}" target="_blank" rel="noopener noreferrer"
                               class="text-gray-300 hover:text-white transition leading-snug block">{{ $cAddress }}</a>
                            @else
                            <p class="text-gray-300 leading-snug">{{ $cAddress }}</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Office hours --}}
                    @if($cHours)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-300 leading-snug">{{ $cHours }}</p>
                    </div>
                    @endif

                </address>
                @endif
            </div>
        </div>

        {{-- ── Bottom bar ─────────────────────────────────────────────── --}}
        <div class="border-t border-gray-800 pt-6 flex flex-col items-center justify-center gap-4 text-xs text-gray-600">
            <p class="text-center">&copy; {{ date('Y') }} All Rights Reserved. | Al-Bereka Business Management System</p>
            <div class="flex items-center gap-5 flex-wrap justify-center">
                {{-- Legal pages — only shown when configured --}}
                @if($settings->get('legal_privacy_policy') === 'enabled')
                <a href="{{ route('page', 'privacy-policy') }}" class="hover:text-gray-400 transition">Privacy Policy</a>
                @endif
                @if($settings->get('legal_terms') === 'enabled')
                <a href="{{ route('page', 'terms') }}" class="hover:text-gray-400 transition">Terms &amp; Conditions</a>
                @endif
            </div>
        </div>

        {{-- ── Developer credits ─────────────────────────────────────── --}}
        <div class="border-t border-gray-800 mt-6 pt-6 text-center text-xs text-gray-500">
            <p>Developed by: 
                <a href="https://t.me/provide_services2" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="text-indigo-400 hover:text-indigo-300 hover:underline transition font-semibold">OmniTech Software Development Company</a>
                <span class="text-gray-600 mx-2">•</span>
                <span class="text-gray-600">Phone:</span>
                <a href="tel:+251910089936" class="text-indigo-400 hover:text-indigo-300 transition ml-1">0910089936</a>
            </p>
        </div>
    </div>
</footer>

{{-- ══ ORDER SUCCESS POPUP ════════════════════════════════════════════════ --}}
@if(session('order_success'))
<div x-data="{ open: true }" x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md text-center p-8"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         @click.stop>

        {{-- Logo --}}
        <div class="flex flex-col items-center mb-6">
            @php $logoPath = app(\App\Services\SettingsService::class)->get('system_logo_path',''); @endphp
            @if($logoPath)
                <img src="{{ asset('storage/' . $logoPath) }}" alt="Logo" class="h-14 w-14 object-contain rounded-2xl shadow mb-2">
            @else
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow mb-2">
                    <span class="text-white font-black text-2xl">A</span>
                </div>
            @endif
            <p class="font-black text-gray-900 leading-tight">Al-Baraka</p>
            <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Business Group</p>
        </div>

        {{-- Success icon --}}
        <div class="w-20 h-20 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
            <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h2 class="text-2xl font-black text-gray-900 mb-2">Order Placed!</h2>
        <p class="text-gray-500 text-sm mb-5">Thank you! We received your order successfully.</p>

        {{-- Order number --}}
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-2xl px-6 py-4 mb-5 inline-block w-full">
            <p class="text-xs text-purple-500 font-bold uppercase tracking-wider mb-1">Your Order Number</p>
            <p class="text-2xl font-black text-purple-700 tracking-wide">{{ session('order_success') }}</p>
        </div>

        <p class="text-gray-400 text-xs mb-6">
            We will contact you on your phone to confirm delivery.<br>
            Please keep your phone available.
        </p>

        {{-- Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('store.products') }}"
               class="flex-1 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-black rounded-xl transition shadow-lg text-sm">
                🛍️ Continue Shopping
            </a>
            <button @click="open = false"
               class="flex-1 py-3 border-2 border-gray-200 hover:border-purple-300 text-gray-600 hover:text-purple-700 font-bold rounded-xl transition text-sm">
                🏠 Stay Here
            </button>
        </div>
    </div>
</div>
@endif

{{-- ══ CART DRAWER ══════════════════════════════════════════════════════════ --}}
@php
    $drawerItems = session('guest_cart', []);
    $drawerTotal = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $drawerItems));
@endphp

{{-- Backdrop --}}
<div x-show="cartOpen" x-cloak
     @click="cartOpen = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40"></div>

{{-- Centered Modal --}}
<div x-show="cartOpen" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-50 flex items-center justify-center p-4">

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h2 class="font-black text-gray-900 text-lg">Your Cart</h2>
                @if(count($drawerItems) > 0)
                <span class="px-2.5 py-0.5 bg-purple-100 text-purple-700 text-xs font-black rounded-full">{{ count($drawerItems) }} items</span>
                @endif
            </div>
            <button @click="cartOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto">

            @if(empty($drawerItems))
            {{-- Empty --}}
            <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                <div class="w-20 h-20 bg-purple-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="font-bold text-gray-700 mb-1">Your cart is empty</p>
                <p class="text-gray-400 text-sm mb-5">Browse products and add items</p>
                <button @click="cartOpen = false"
                    class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-xl text-sm">
                    Browse Products
                </button>
            </div>

            @else
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- LEFT: Cart Items --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Items</h3>
                    <div class="space-y-2" id="cart-items-list">
                        @foreach($drawerItems as $productId => $item)
                        <div id="cart-item-{{ $productId }}" class="flex items-center gap-3 bg-gray-50 rounded-2xl p-3">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white border border-gray-200 shrink-0 flex items-center justify-center">
                                @if(!empty($item['image']))
                                    <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-xl">📦</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 text-xs">{{ $item['name'] }}</p>
                                <p id="price-{{ $productId }}" data-price="{{ $item['price'] }}" data-product-id="{{ $productId }}"
                                   class="text-purple-600 font-black text-xs">ETB {{ number_format($item['price'], 2) }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button id="minus-{{ $productId }}"
                                   onclick="updateCart({{ $productId }}, {{ max(1, $item['quantity'] - 1) }})"
                                   class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-200 hover:bg-purple-100 hover:text-purple-700 font-black text-sm transition">−</button>
                                <span id="qty-{{ $productId }}" class="w-6 text-center font-black text-sm text-gray-900">{{ $item['quantity'] }}</span>
                                <button id="plus-{{ $productId }}"
                                   onclick="updateCart({{ $productId }}, {{ $item['quantity'] + 1 }})"
                                   class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-200 hover:bg-purple-100 hover:text-purple-700 font-black text-sm transition">+</button>
                            </div>
                            <div class="text-right shrink-0">
                                <p id="linetotal-{{ $productId }}" class="font-black text-gray-900 text-xs">ETB {{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                                <button onclick="removeCart({{ $productId }})"
                                   class="text-red-400 hover:text-red-600 text-[10px] font-semibold">Remove</button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Total --}}
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="font-bold text-gray-600 text-sm">Total</span>
                        <span id="cart-grand-total" class="font-black text-lg text-gray-900">ETB {{ number_format($drawerTotal, 2) }}</span>
                    </div>
                </div>

                {{-- RIGHT: Customer Info Form --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Your Details</h3>

                    {{-- Validation errors --}}
                    @if(session()->has('errors') || (isset($errors) && $errors->any()))
                    <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-xs font-semibold">
                        @foreach((isset($errors) ? $errors->all() : []) as $error)
                        <p>❌ {{ $error }}</p>
                        @endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ route('guest.cart.order') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Full Name *</label>
                            <input type="text" name="customer_name" required minlength="2"
                                value="{{ old('customer_name') }}"
                                placeholder="Ahmed Mohammed"
                                class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:border-purple-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Phone Number *</label>
                            <input type="tel" name="customer_phone" required minlength="7"
                                value="{{ old('customer_phone') }}"
                                placeholder="0912345678"
                                class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:border-purple-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Email (optional)</label>
                            <input type="email" name="customer_email"
                                value="{{ old('customer_email') }}"
                                placeholder="your@email.com"
                                class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:border-purple-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Delivery Address *</label>
                            <textarea name="address" required minlength="5" rows="2"
                                placeholder="Your full delivery address"
                                class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:border-purple-500 outline-none transition resize-none">{{ old('address') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Notes (optional)</label>
                            <textarea name="notes" rows="2"
                                placeholder="Special instructions..."
                                class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:border-purple-500 outline-none transition resize-none">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit"
                            class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-black text-sm rounded-xl transition shadow-lg">
                            ✅ Place Order
                        </button>
                        <p class="text-xs text-gray-400 text-center">No account needed. We'll call to confirm.</p>
                    </form>
                </div>

            </div>
            @endif
        </div>
    </div>
</div>
@livewireScripts

<script>
var cartBase = '{{ url("/guest/cart") }}';

function updateCart(productId, qty) {
    fetch(cartBase + '/update?product_id=' + productId + '&qty=' + qty, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Update qty number shown in popup
        var qtyEl = document.getElementById('qty-' + productId);
        if (qtyEl) qtyEl.textContent = qty;

        // Update the minus button's new target qty
        var minusBtn = document.getElementById('minus-' + productId);
        if (minusBtn) minusBtn.setAttribute('onclick', "updateCart(" + productId + "," + Math.max(1, qty-1) + ")");

        // Update the plus button's new target qty
        var plusBtn = document.getElementById('plus-' + productId);
        if (plusBtn) plusBtn.setAttribute('onclick', "updateCart(" + productId + "," + (qty+1) + ")");

        // Update line total
        var totalEl = document.getElementById('linetotal-' + productId);
        var price = parseFloat(document.getElementById('price-' + productId).getAttribute('data-price'));
        if (totalEl) totalEl.textContent = 'ETB ' + (price * qty).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

        // Update cart grand total
        updateGrandTotal();

        // Update cart badge count
        var badge = document.getElementById('cart-count-badge');
        if (badge) badge.textContent = data.count;
    });
}

function removeCart(productId) {
    fetch(cartBase + '/remove?product_id=' + productId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Remove the item row from DOM
        var row = document.getElementById('cart-item-' + productId);
        if (row) row.remove();

        // Update total
        updateGrandTotal();

        // Update badge
        var badge = document.getElementById('cart-count-badge');
        if (badge) badge.textContent = data.count;

        // If cart empty, show empty state
        if (data.count === 0) {
            var itemsDiv = document.getElementById('cart-items-list');
            if (itemsDiv) itemsDiv.innerHTML = '<div class="flex flex-col items-center justify-center py-16 text-center px-6"><div class="w-20 h-20 bg-purple-50 rounded-full flex items-center justify-center mb-4"><svg class="w-10 h-10 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></div><p class="font-bold text-gray-700">Your cart is empty</p></div>';
            var footer = document.getElementById('cart-footer');
            if (footer) footer.style.display = 'none';
        }
    });
}

function updateGrandTotal() {
    var total = 0;
    document.querySelectorAll('[data-line-total]').forEach(function(el) {
        total += parseFloat(el.getAttribute('data-line-total-value') || 0);
    });
    // Recalculate from all price * qty
    total = 0;
    document.querySelectorAll('[data-price]').forEach(function(el) {
        var productId = el.getAttribute('data-product-id');
        var price = parseFloat(el.getAttribute('data-price'));
        var qtyEl = document.getElementById('qty-' + productId);
        if (qtyEl) total += price * parseInt(qtyEl.textContent);
    });
    var grandEl = document.getElementById('cart-grand-total');
    if (grandEl) grandEl.textContent = 'ETB ' + total.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.documentElement.style.visibility = 'visible';
        document.documentElement.removeAttribute('style');
    });
    setTimeout(function() {
        document.documentElement.style.visibility = 'visible';
    }, 800);
</script>
</body>
</html>
