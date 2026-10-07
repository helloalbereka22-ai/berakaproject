<div>
    <form wire:submit="save" class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">SEO & Website Settings</h2>
                <p class="text-sm text-slate-400 mt-1">Manage global search engine optimization and organization location.</p>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Save Settings
            </button>
        </div>

        @if($successMsg)
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">
                {{ $successMsg }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- System Branding --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                <div>
                    <h3 class="font-bold text-white text-sm uppercase tracking-wider mb-1">System Branding</h3>
                    <p class="text-xs text-slate-400">Upload your system logo and login background image.</p>
                </div>

                <div class="space-y-4">
                    {{-- System Logo --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">System Logo</label>
                        
                        @if($currentLogoPath)
                        <div class="mb-3 p-4 bg-slate-950/50 border-2 border-slate-700/50 rounded-xl">
                            <img src="{{ asset('storage/' . $currentLogoPath) }}" alt="Current Logo" class="h-20 object-contain">
                            <p class="text-xs text-slate-500 mt-2">Current logo</p>
                        </div>
                        @endif

                        <div class="relative">
                            <input wire:model="systemLogo" type="file" accept="image/*" id="systemLogo" class="hidden">
                            <label for="systemLogo" 
                                class="flex items-center justify-center gap-2 w-full bg-slate-950/80 border-2 border-dashed border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-slate-400 hover:text-indigo-400 hover:border-indigo-500/50 focus:border-indigo-500 outline-none transition-all cursor-pointer shadow-lg group">
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $currentLogoPath ? 'Change Logo' : 'Upload Logo' }}
                            </label>
                        </div>
                        @error('systemLogo') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        
                        @if($systemLogo)
                        <div class="mt-3 flex items-center gap-3 p-4 bg-indigo-950/50 border-2 border-indigo-700/50 rounded-xl">
                            <img src="{{ $systemLogo->temporaryUrl() }}" alt="Preview" class="h-16 w-16 object-cover rounded-lg border-2 border-indigo-500/50">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-indigo-300">New Logo Selected</p>
                                <p class="text-xs text-slate-500 mt-1">Will be saved when you click Save Settings</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Login Background --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Login Background</label>
                        
                        @if($currentBgPath)
                        <div class="mb-3 p-4 bg-slate-950/50 border-2 border-slate-700/50 rounded-xl">
                            <img src="{{ asset('storage/' . $currentBgPath) }}" alt="Current Background" class="h-32 w-full object-cover rounded-lg">
                            <p class="text-xs text-slate-500 mt-2">Current background</p>
                        </div>
                        @endif

                        <div class="relative">
                            <input wire:model="loginBackground" type="file" accept="image/*" id="loginBackground" class="hidden">
                            <label for="loginBackground" 
                                class="flex items-center justify-center gap-2 w-full bg-slate-950/80 border-2 border-dashed border-slate-700/70 rounded-xl px-4 py-3 text-sm font-semibold text-slate-400 hover:text-indigo-400 hover:border-indigo-500/50 focus:border-indigo-500 outline-none transition-all cursor-pointer shadow-lg group">
                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $currentBgPath ? 'Change Background' : 'Upload Background' }}
                            </label>
                        </div>
                        @error('loginBackground') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        
                        @if($loginBackground)
                        <div class="mt-3 p-4 bg-indigo-950/50 border-2 border-indigo-700/50 rounded-xl">
                            <img src="{{ $loginBackground->temporaryUrl() }}" alt="Preview" class="h-32 w-full object-cover rounded-lg border-2 border-indigo-500/50">
                            <p class="text-sm font-bold text-indigo-300 mt-2">New Background Selected</p>
                            <p class="text-xs text-slate-500 mt-1">Will be saved when you click Save Settings</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Promo Slider Images --}}
            <div class="bg-slate-900 border border-purple-500/30 rounded-2xl p-6 space-y-5 lg:col-span-2">
                <div>
                    <h3 class="font-bold text-purple-300 text-sm uppercase tracking-wider mb-1">🖼️ Homepage Promotional Slider</h3>
                    <p class="text-xs text-slate-400">Upload up to 5 images to display as a sliding banner on the homepage. Images replace the default hero section.</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach(range(1,5) as $i)
                    @php
                        $propName = "promoImage{$i}";
                        $curName  = "currentPromo{$i}";
                    @endphp
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Slide {{ $i }}</p>

                        {{-- Current image preview --}}
                        @if($this->$curName)
                        <div class="relative rounded-xl overflow-hidden border-2 border-purple-500/30 aspect-video bg-slate-950">
                            <img src="{{ asset('storage/' . $this->$curName) }}"
                                 alt="Promo {{ $i }}"
                                 class="w-full h-full object-cover">
                            <span class="absolute top-1 right-1 px-1.5 py-0.5 bg-green-500 text-white text-[10px] font-bold rounded">Live</span>
                        </div>
                        @endif

                        {{-- New upload preview --}}
                        @if($this->$propName)
                        <div class="rounded-xl overflow-hidden border-2 border-indigo-500/50 aspect-video bg-slate-950">
                            <img src="{{ $this->$propName->temporaryUrl() }}" class="w-full h-full object-cover">
                        </div>
                        @endif

                        {{-- Upload button --}}
                        <input wire:model="promoImage{{ $i }}" type="file" accept="image/*"
                               id="promoImage{{ $i }}" class="hidden">
                        <label for="promoImage{{ $i }}"
                               class="flex items-center justify-center gap-1.5 w-full border-2 border-dashed border-slate-700 hover:border-purple-500 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-400 hover:text-purple-300 cursor-pointer transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $this->$curName ? 'Change' : 'Upload' }}
                        </label>
                        @error("promoImage{$i}") <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-slate-500">Recommended size: 1400×500px. JPG or PNG. Max 5MB each. Click Save Settings to apply.</p>
            </div>

            {{-- SEO Configuration --}}
        </div>
    </form>
</div>
