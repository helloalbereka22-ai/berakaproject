<div>

@php
    $aboutContent = app(\App\Services\SettingsService::class)->get('about_us_content', '');
    $lines    = explode("\n", trim($aboutContent), 2);
    $abTitle  = trim($lines[0] ?? '');
    $abBody   = trim($lines[1] ?? '');
@endphp

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
    <div class="absolute inset-0 opacity-10"
         style="background-image: radial-gradient(circle at 25% 40%, #6366f1 0%, transparent 55%), radial-gradient(circle at 75% 70%, #3b82f6 0%, transparent 55%);">
    </div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-500/20 border border-indigo-400/30
                     text-indigo-300 text-xs font-bold rounded-full uppercase tracking-widest mb-6">
            Our Story
        </span>
        <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight mb-6 leading-tight">
            {{ __('app.about.title') }}
        </h1>
        <p class="text-indigo-200 text-xl max-w-2xl mx-auto leading-relaxed">
            {{ __('app.about.description') }}
        </p>
    </div>
</div>

{{-- ── GM-managed About Content ─────────────────────────────────────────── --}}
@if($aboutContent)
<section class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">{{ __('app.about.overview') }}</span>
                @if($abTitle)
                <h2 class="text-3xl font-extrabold text-gray-900 mt-3 mb-5 leading-tight">{{ $abTitle }}</h2>
                @endif
                @if($abBody)
                <p class="text-gray-600 leading-relaxed text-lg whitespace-pre-line mb-8">{{ $abBody }}</p>
                @endif
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('manufacturing') }}"
                       class="flex items-center justify-center gap-2 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl transition text-sm shadow-lg shadow-amber-100">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        {{ __('app.nav_pub.manufacturing') }}
                    </a>
                    <a href="{{ route('bookstore') }}"
                       class="flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition text-sm shadow-lg shadow-emerald-100">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        {{ __('app.nav_pub.bookstore') }}
                    </a>
                </div>
            </div>
            {{-- Decorative right side --}}
            <div class="hidden lg:flex flex-col gap-4">
                @foreach([
                    ['icon'=>'🏭','label'=>'Oil Manufacturing','sub'=>'Lubricating oil factory','color'=>'amber'],
                    ['icon'=>'📚','label'=>'Bookstore Network','sub'=>'Multi-branch retail','color'=>'emerald'],
                    ['icon'=>'🌍','label'=>'Community Focus','sub'=>'Serving local markets','color'=>'indigo'],
                ] as $feat)
                <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-100 rounded-2xl hover:border-indigo-200 hover:bg-indigo-50/30 transition">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $feat['color'] }}-100 flex items-center justify-center text-2xl shrink-0">
                        {{ $feat['icon'] }}
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 text-sm">{{ $feat['label'] }}</p>
                        <p class="text-gray-500 text-xs">{{ $feat['sub'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- ── Vision / Mission / Values ────────────────────────────────────────── --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">What drives us</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-3">Our Foundation</h2>
        </div>
        @php
            $svc         = app(\App\Services\SettingsService::class);
            $visionText  = $svc->get('vision_text',  __('app.about.vision_text'));
            $missionText = $svc->get('mission_text', __('app.about.mission_text'));
            $valuesText  = $svc->get('values_text',  implode(' · ', [__('app.about.value_quality'), __('app.about.value_integrity'), __('app.about.value_service'), __('app.about.value_community')]));
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['icon' => '🎯', 'title' => __('app.about.vision'),  'body' => $visionText,  'color' => 'blue',    'bg' => 'from-blue-50'],
                ['icon' => '🚀', 'title' => __('app.about.mission'), 'body' => $missionText, 'color' => 'indigo',  'bg' => 'from-indigo-50'],
                ['icon' => '💎', 'title' => __('app.about.values'),  'body' => $valuesText,  'color' => 'emerald', 'bg' => 'from-emerald-50'],
            ] as $card)
            <div class="relative bg-gradient-to-br {{ $card['bg'] }} to-white rounded-3xl border border-gray-100
                        p-8 hover:shadow-xl hover:-translate-y-1 transition-all duration-200">
                <div class="w-14 h-14 rounded-2xl bg-{{ $card['color'] }}-100 border border-{{ $card['color'] }}-200
                            flex items-center justify-center text-3xl mb-6 shadow-sm">
                    {{ $card['icon'] }}
                </div>
                <h3 class="font-extrabold text-gray-900 text-lg mb-3">{{ $card['title'] }}</h3>
                <p class="text-gray-600 leading-relaxed text-sm">{{ $card['body'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Business units ───────────────────────────────────────────────────── --}}
@if($businesses->isNotEmpty())
<section class="py-20 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Our portfolio</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-3">{{ __('app.organizations.title') }}</h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto">{{ __('app.organizations.subtitle') }}</p>
        </div>
        <div class="space-y-6">
            @foreach($businesses as $biz)
            <div class="group bg-white border border-gray-200 rounded-3xl p-8 hover:border-indigo-300 hover:shadow-lg transition-all duration-200">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-5 mb-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-2xl shrink-0">
                            🏢
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900">{{ $biz->name }}</h3>
                            <span class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-0.5 text-xs font-semibold rounded-full
                                         {{ $biz->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $biz->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ ucfirst($biz->status) }}
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('organizations') }}"
                       class="shrink-0 flex items-center gap-1.5 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition shadow-lg shadow-indigo-100">
                        {{ __('app.organizations.learn_more') }}
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                @if($biz->description)
                <p class="text-gray-500 text-sm mb-5 ml-16 leading-relaxed">{{ $biz->description }}</p>
                @endif
                @if($biz->branches->isNotEmpty())
                <div class="ml-16">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">{{ __('app.organizations.our_branches') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($biz->branches as $branch)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200
                                     rounded-xl text-xs font-medium text-gray-700 group-hover:border-indigo-200 transition">
                            <svg class="w-3 h-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $branch->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

</div>
