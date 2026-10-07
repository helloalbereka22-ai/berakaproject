<div class="space-y-6 max-w-5xl">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-100">Organization & Contact Settings</h1>
            <p class="text-slate-400 text-sm mt-0.5">Manage public contact information, branding, and social links. Changes reflect immediately on the public website.</p>
        </div>
        {{-- Preview link --}}
        <a href="{{ route('about') }}" target="_blank"
           class="flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Preview Public Site
        </a>
    </div>

    {{-- Success message --}}
    @if($successMsg)
    <div class="flex items-center gap-2.5 px-4 py-3 bg-emerald-900/40 border border-emerald-700/50 text-emerald-300 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        {{ $successMsg }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-slate-800">
        @foreach([
            'organization' => ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Organization'],
            'contact'      => ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'label' => 'Contact'],
            'social'       => ['icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', 'label' => 'Social Media'],
            'preview'      => ['icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'Preview'],
        ] as $tab => $meta)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold transition border-b-2
                   {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta['icon'] }}"/>
            </svg>
            {{ $meta['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ═══ ORGANIZATION TAB ═══════════════════════════════════════════════ --}}
    @if($activeTab === 'organization')
    <form wire:submit="saveOrganization" class="bg-slate-900/60 border border-slate-800/60 rounded-2xl p-6 space-y-5">
        <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wider mb-1">Organization Information</h2>
        <p class="text-xs text-slate-500 mb-4">Displayed in the footer, About page, and homepage hero.</p>

        {{-- Name --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                Organization Name <span class="text-red-500">*</span>
            </label>
            <input wire:model="orgName" type="text" required
                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                       @error('orgName') border-red-500 @enderror">
            @error('orgName')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Tagline --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Tagline</label>
            <input wire:model="orgTagline" type="text"
                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition"
                placeholder="Premium Oils & Curated Books">
            @error('orgTagline')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Short Description</label>
            <textarea wire:model="orgDescription" rows="3"
                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition resize-none"
                placeholder="Used in the footer and About page."></textarea>
            @error('orgDescription')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Website --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Website URL</label>
            <input wire:model="orgWebsite" type="url"
                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition"
                placeholder="https://albareka.com">
            @error('orgWebsite')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Logo --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Logo</label>
            @if(!empty($preview['org_logo_path']))
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ Storage::url($preview['org_logo_path']) }}" alt="Logo"
                     class="h-12 w-auto rounded-lg object-contain bg-white p-1">
                <span class="text-xs text-slate-500">Current logo</span>
            </div>
            @endif
            <input wire:model="orgLogo" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                class="w-full bg-slate-800 border border-slate-700 text-slate-400 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:bg-slate-700 file:text-slate-300 file:border-0 file:px-3 file:py-1 file:rounded-lg file:text-xs">
            @error('orgLogo')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            <p class="text-[10px] text-slate-600 mt-1">Max 2MB · JPG, PNG, WebP, SVG</p>
        </div>

        <div class="pt-2">
            <button type="submit"
                wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition flex items-center gap-2">
                <svg wire:loading wire:target="saveOrganization" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="saveOrganization">Save Organization Info</span>
                <span wire:loading wire:target="saveOrganization">Saving...</span>
            </button>
        </div>
    </form>
    @endif

    {{-- ═══ CONTACT TAB ════════════════════════════════════════════════════ --}}
    @if($activeTab === 'contact')
    <form wire:submit="saveContact" class="bg-slate-900/60 border border-slate-800/60 rounded-2xl p-6 space-y-5">
        <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wider mb-1">Contact Information</h2>
        <p class="text-xs text-slate-500 mb-4">Displayed in the footer and Contact page. Leave blank to hide.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Primary phone --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Primary Phone</label>
                <input wire:model="contactPhone" type="text"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                           @error('contactPhone') border-red-500 @enderror"
                    placeholder="+251 9 ...">
                @error('contactPhone')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            {{-- Secondary phone --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Secondary Phone <span class="text-slate-600 normal-case font-normal">(optional)</span></label>
                <input wire:model="contactPhoneSecondary" type="text"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition"
                    placeholder="+251 9 ...">
            </div>
            {{-- Primary email --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Primary Email</label>
                <input wire:model="contactEmail" type="email"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                           @error('contactEmail') border-red-500 @enderror"
                    placeholder="info@albareka.com">
                @error('contactEmail')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            {{-- Secondary email --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Secondary Email <span class="text-slate-600 normal-case font-normal">(optional)</span></label>
                <input wire:model="contactEmailSecondary" type="email"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                           @error('contactEmailSecondary') border-red-500 @enderror"
                    placeholder="support@albareka.com">
                @error('contactEmailSecondary')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Address --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Physical Address</label>
            <textarea wire:model="contactAddress" rows="2"
                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition resize-none
                       @error('contactAddress') border-red-500 @enderror"
                placeholder="Street, City, Country"></textarea>
            @error('contactAddress')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Office hours --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Office Hours</label>
                <input wire:model="contactOfficeHours" type="text"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition"
                    placeholder="Mon–Sat 8:00–17:00">
            </div>
            {{-- Google Maps URL --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Google Maps URL <span class="text-slate-600 normal-case font-normal">(optional)</span></label>
                <input wire:model="contactMapsUrl" type="url"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                           @error('contactMapsUrl') border-red-500 @enderror"
                    placeholder="https://maps.google.com/...">
                @error('contactMapsUrl')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pt-2">
            <button type="submit"
                wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition flex items-center gap-2">
                <svg wire:loading wire:target="saveContact" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="saveContact">Save Contact Info</span>
                <span wire:loading wire:target="saveContact">Saving...</span>
            </button>
        </div>
    </form>
    @endif

    {{-- ═══ SOCIAL MEDIA TAB ═══════════════════════════════════════════════ --}}
    @if($activeTab === 'social')
    <form wire:submit="saveSocial" class="bg-slate-900/60 border border-slate-800/60 rounded-2xl p-6 space-y-5">
        <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wider mb-1">Social Media Links</h2>
        <p class="text-xs text-slate-500 mb-4">Enter full URLs (https://...). Leave blank to hide from the footer. Invalid URLs will not be saved.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach([
                ['field' => 'socialFacebook',  'label' => 'Facebook',  'placeholder' => 'https://facebook.com/albareka',  'color' => 'blue'],
                ['field' => 'socialTelegram',  'label' => 'Telegram',  'placeholder' => 'https://t.me/albareka',          'color' => 'sky'],
                ['field' => 'socialYoutube',   'label' => 'YouTube',   'placeholder' => 'https://youtube.com/@albareka',  'color' => 'red'],
                ['field' => 'socialLinkedin',  'label' => 'LinkedIn',  'placeholder' => 'https://linkedin.com/company/albareka', 'color' => 'blue'],
                ['field' => 'socialInstagram', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/albareka', 'color' => 'pink'],
                ['field' => 'socialTwitter',   'label' => 'X / Twitter','placeholder' => 'https://x.com/albareka',       'color' => 'slate'],
                ['field' => 'socialWhatsapp',  'label' => 'WhatsApp',  'placeholder' => 'https://wa.me/251...',          'color' => 'green'],
                ['field' => 'socialTiktok',    'label' => 'TikTok',    'placeholder' => 'https://tiktok.com/@albareka',  'color' => 'slate'],
            ] as $social)
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                    {{ $social['label'] }}
                </label>
                <div class="relative">
                    <input wire:model="{{ $social['field'] }}" type="url"
                        class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-xl ps-4 pe-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 transition
                               @error($social['field']) border-red-500 @enderror"
                        placeholder="{{ $social['placeholder'] }}">
                </div>
                @error($social['field'])<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            @endforeach
        </div>

        <div class="pt-2">
            <button type="submit"
                wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition flex items-center gap-2">
                <svg wire:loading wire:target="saveSocial" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="saveSocial">Save Social Links</span>
                <span wire:loading wire:target="saveSocial">Saving...</span>
            </button>
        </div>
    </form>
    @endif

    {{-- ═══ PREVIEW TAB ════════════════════════════════════════════════════ --}}
    @if($activeTab === 'preview')
    <div class="bg-slate-900/60 border border-slate-800/60 rounded-2xl p-6 space-y-5">
        <h2 class="font-bold text-slate-100 text-sm uppercase tracking-wider mb-1">Footer Preview</h2>
        <p class="text-xs text-slate-500 mb-4">This is how your information will appear in the public footer.</p>

        {{-- Mini footer preview --}}
        <div class="bg-gray-950 rounded-2xl p-6 text-gray-400 text-sm">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                {{-- Org column --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        @if(!empty($preview['org_logo_path']))
                            <img src="{{ Storage::url($preview['org_logo_path']) }}" alt="Logo"
                                 class="h-8 w-auto object-contain rounded bg-white p-0.5">
                        @else
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-700 to-indigo-600 flex items-center justify-center">
                                <span class="text-white font-extrabold text-sm">A</span>
                            </div>
                        @endif
                        <div>
                            <p class="text-white font-extrabold text-sm">{{ $preview['org_name'] ?? 'Al-Baraka' }}</p>
                            @if(!empty($preview['org_tagline']))
                            <p class="text-indigo-400 text-[10px] uppercase tracking-widest">{{ $preview['org_tagline'] }}</p>
                            @endif
                        </div>
                    </div>
                    @if(!empty($preview['org_description']))
                    <p class="text-xs text-gray-500 leading-relaxed">{{ Str::limit($preview['org_description'], 100) }}</p>
                    @endif
                </div>

                {{-- Contact column --}}
                <div>
                    <p class="text-white text-xs font-bold uppercase tracking-wider mb-3">Contact</p>
                    <div class="space-y-1.5 text-xs">
                        @foreach([
                            ['key' => 'contact_phone',   'prefix' => 'tel:',    'icon' => '📞'],
                            ['key' => 'contact_email',   'prefix' => 'mailto:', 'icon' => '✉️'],
                            ['key' => 'contact_address', 'prefix' => '',        'icon' => '📍'],
                            ['key' => 'contact_office_hours', 'prefix' => '', 'icon' => '🕐'],
                        ] as $item)
                            @if(!empty($preview[$item['key']]))
                            <div class="flex items-start gap-2">
                                <span class="shrink-0">{{ $item['icon'] }}</span>
                                <span class="text-gray-400">{{ $preview[$item['key']] }}</span>
                            </div>
                            @endif
                        @endforeach
                        @if(empty($preview['contact_phone']) && empty($preview['contact_email']) && empty($preview['contact_address']))
                        <p class="text-gray-600 italic">No contact info configured yet.</p>
                        @endif
                    </div>
                </div>

                {{-- Social column --}}
                <div>
                    <p class="text-white text-xs font-bold uppercase tracking-wider mb-3">Social</p>
                    @php
                        $activeSocials = array_filter(
                            array_intersect_key($preview, array_flip(\App\Services\SettingsService::SOCIAL_KEYS)),
                            fn($v) => !empty(trim((string)$v)) && filter_var(trim($v), FILTER_VALIDATE_URL)
                        );
                    @endphp
                    @if(!empty($activeSocials))
                    <div class="flex flex-wrap gap-2">
                        @foreach($activeSocials as $key => $url)
                        <a href="{{ $url }}" target="_blank"
                           class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition text-xs font-bold">
                            {{ strtoupper(substr(str_replace('social_', '', $key), 0, 2)) }}
                        </a>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-600 text-xs italic">No social links configured.</p>
                    @endif
                </div>
            </div>
            <div class="border-t border-gray-800 pt-4 text-xs text-gray-600 text-center">
                © {{ date('Y') }} {{ $preview['org_name'] ?? 'Al-Baraka' }}. All Rights Reserved.
            </div>
        </div>

        <a href="{{ route('home') }}" target="_blank"
           class="inline-flex items-center gap-2 text-xs text-indigo-400 hover:text-indigo-300 transition font-semibold">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Open live public website in new tab →
        </a>
    </div>
    @endif

</div>
