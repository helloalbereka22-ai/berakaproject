<div class="max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.products.index') }}" wire:navigate
            class="p-2 rounded-xl text-slate-500 hover:text-white hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            @php
                $typeIcon = match($productType) { 'book' => '📚', 'stationery' => '✏️', 'fashion' => '👗', default => '📦' };
                $typeLabel = match($productType) { 'book' => 'Book', 'stationery' => 'Stationery', 'fashion' => 'Fashion', default => 'Product' };
            @endphp
            <h2 class="text-2xl font-bold text-white">Edit {{ $typeIcon }} {{ $typeLabel }}</h2>
            <p class="text-sm text-slate-400 mt-0.5">Update product details and pricing.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">

        {{-- ── SECTION 1: Classification ──────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Classification</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Category <span class="text-red-400">*</span></label>
                    <select wire:model.live="categoryId" id="categoryId"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select category...</option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}" @selected($c['id'] == $categoryId)>{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── SECTION 2: Product Details ─────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">{{ ucfirst($typeLabel) }} Details</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        {{ $productType === 'book' ? 'Title' : 'Product Name' }} <span class="text-red-400">*</span>
                    </label>
                    <input wire:model="name" type="text" id="name"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Book-specific --}}
                @if($productType === 'book')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Author</label>
                    <input wire:model="author" type="text" id="author"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Publisher</label>
                    <input wire:model="publisher" type="text" id="publisher"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">ISBN</label>
                    <input wire:model="isbn" type="text" id="isbn"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Edition</label>
                    <input wire:model="edition" type="text" id="edition"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Language</label>
                    <input wire:model="language" type="text" id="language"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                @endif

                {{-- Fashion-specific --}}
                @if($productType === 'fashion')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Brand</label>
                    <input wire:model="brand" type="text" id="brand"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Size</label>
                    <input wire:model="size" type="text" id="size"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Color</label>
                    <input wire:model="color" type="text" id="color"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Material</label>
                    <input wire:model="material" type="text" id="material"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Gender</label>
                    <select wire:model="gender" id="gender"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select...</option>
                        @foreach($genderOptions as $g)<option value="{{ $g }}" @selected($g === $gender)>{{ $g }}</option>@endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">SKU</label>
                    <input wire:model="sku" type="text" id="sku"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('sku') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Barcode</label>
                    <input wire:model="barcode" type="text" id="barcode"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Description</label>
                    <textarea wire:model="description" rows="3" id="description"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none resize-none"></textarea>
                </div>

                {{-- Image --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Product Image</label>
                    @if($currentImage)
                        <div class="mb-2 flex items-center gap-3">
                            <img src="{{ Storage::url($currentImage) }}" alt="Current image" class="w-16 h-16 rounded-xl object-cover border border-slate-700">
                            <p class="text-xs text-slate-500">Current image. Upload below to replace.</p>
                        </div>
                    @endif
                    <input wire:model="image" type="file" accept="image/*" id="image"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-300 file:mr-3 file:bg-indigo-600 file:text-white file:text-xs file:font-semibold file:border-0 file:rounded-lg file:px-3 file:py-1.5 focus:border-indigo-500 outline-none">
                    @error('image') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── SECTION 3: Pricing ─────────────────────────────────────── --}}
        <div>
            <div class="flex items-center gap-2 mb-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pricing</p>
                <span class="px-2 py-0.5 rounded-full text-xs bg-red-500/10 text-red-400 border border-red-500/20 font-semibold">Admin Only</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Cost / Purchase Price (ETB) <span class="text-red-400">*</span></label>
                    <input wire:model.live="costPrice" type="number" step="0.01" min="0" id="costPrice"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('costPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Selling Price (ETB) <span class="text-red-400">*</span></label>
                    <input wire:model.live="sellingPrice" type="number" step="0.01" min="0" id="sellingPrice"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('sellingPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Discount Price (ETB) <span class="text-slate-500 font-normal text-xs">— Leave blank for no discount</span></label>
                    <input wire:model.live="discountPrice" type="number" step="0.01" min="0" id="discountPrice"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('discountPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    @if($discountAmount > 0)
                        <div class="mt-1.5 flex items-center gap-3">
                            <span class="text-xs text-emerald-400 font-semibold">✓ Discount: {{ number_format($discountAmount, 2) }} ETB</span>
                            <span class="text-xs text-emerald-400">({{ number_format($discountPercentage, 1) }}% off)</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- ── SECTION 4: Visibility ─────────────────────────────────── --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Visibility</p>
            <select wire:model="status" id="status"
                class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($key === $status)>{{ $label }}</option>@endforeach
            </select>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
            <a href="{{ route('admin.products.index') }}" wire:navigate
                class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</a>
            <button wire:click="save" id="save-edit-btn"
                class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">
                Save Changes
            </button>
        </div>
    </div>
</div>
