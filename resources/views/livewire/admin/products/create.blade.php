<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.products.index') }}" wire:navigate class="p-2 rounded-xl text-slate-500 hover:text-white hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-white">Create Product</h2>
            <p class="text-sm text-slate-400 mt-0.5">Add a new product to the shared catalog.</p>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">

        {{-- Classification --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Classification</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Business <span class="text-red-400">*</span></label>
                    <select wire:model.live="businessId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select business...</option>
                        @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
                    </select>
                    @error('businessId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Category <span class="text-red-400">*</span></label>
                    <select wire:model="categoryId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500" {{ empty($categories) ? 'disabled' : '' }}>
                        <option value="">Select category...</option>
                        @foreach($categories as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach
                    </select>
                    @error('categoryId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Product Type <span class="text-red-400">*</span></label>
                    <select wire:model.live="type" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        @foreach($productTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    @error('type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- Identity --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Product Identity</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Product Name <span class="text-red-400">*</span></label>
                    <input wire:model="name" type="text" placeholder="Full product name"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">SKU</label>
                    <input wire:model="sku" type="text" placeholder="Auto-generated if blank"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono placeholder-slate-600 focus:border-indigo-500 outline-none">
                    @error('sku') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Barcode / ISBN</label>
                    <input wire:model="barcode" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                </div>
                @if($type !== \App\Models\Product::TYPE_FINISHED_PRODUCT)
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Unit of Measure</label>
                    <input wire:model="unit" type="text" placeholder="kg, litre, piece, carton..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Package Size</label>
                    <input wire:model="packageSize" type="text" placeholder="e.g. 500ml, 1L, 5L"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none">
                </div>
                @endif
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Description</label>
                    <textarea wire:model="description" rows="3"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none resize-none"></textarea>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- Pricing --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Pricing</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Cost Price <span class="text-red-400">*</span></label>
                    <input wire:model="costPrice" type="number" step="0.0001" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('costPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @if($type !== \App\Models\Product::TYPE_FINISHED_PRODUCT)
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Selling Price <span class="text-red-400">*</span></label>
                    <input wire:model="sellingPrice" type="number" step="0.0001" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('sellingPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Discount Price</label>
                    <input wire:model="discountPrice" type="number" step="0.0001" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('discountPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    @if((float)$discountPrice > 0 && (float)$discountPrice < (float)$sellingPrice)
                        <p class="text-emerald-400 text-xs mt-1 font-bold">Discount: {{ number_format((float)$sellingPrice - (float)$discountPrice, 2) }} ETB ({{ number_format(((float)$sellingPrice - (float)$discountPrice) / (float)$sellingPrice * 100, 2) }}%)</p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        @if($type === \App\Models\Product::TYPE_FINISHED_PRODUCT)
        {{-- Variants / Sizes --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saleable Sizes / Variants <span class="text-red-400">*</span></p>
                <button wire:click="addVariant" type="button" class="text-xs font-bold text-indigo-400 hover:text-indigo-300">
                    + Add Size
                </button>
            </div>
            
            <div class="space-y-4">
                @foreach($variants as $index => $variant)
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-700 grid grid-cols-1 sm:grid-cols-5 gap-3 relative">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Size (e.g. 500ml)</label>
                        <input wire:model="variants.{{ $index }}.size" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-indigo-500">
                        @error('variants.'.$index.'.size') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Unit</label>
                        <input wire:model="variants.{{ $index }}.unit" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-indigo-500">
                        @error('variants.'.$index.'.unit') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Selling Price</label>
                        <input wire:model="variants.{{ $index }}.selling_price" type="number" step="0.01" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white font-mono outline-none focus:border-indigo-500">
                        @error('variants.'.$index.'.selling_price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Discount Price</label>
                        <input wire:model.live="variants.{{ $index }}.discount_price" type="number" step="0.01" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white font-mono outline-none focus:border-indigo-500">
                        @if((float)$variant['discount_price'] > 0 && (float)$variant['selling_price'] > 0 && (float)$variant['discount_price'] < (float)$variant['selling_price'])
                            <p class="text-emerald-400 text-[10px] mt-1 font-bold">{{ number_format(((float)$variant['selling_price'] - (float)$variant['discount_price']) / (float)$variant['selling_price'] * 100, 1) }}% OFF</p>
                        @endif
                    </div>
                    <div class="flex items-end justify-between">
                        <div class="flex-1 mr-2">
                            <label class="block text-xs font-semibold text-slate-400 mb-1">SKU</label>
                            <input wire:model="variants.{{ $index }}.sku" type="text" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white font-mono outline-none focus:border-indigo-500">
                        </div>
                        @if(count($variants) > 1)
                        <button wire:click="removeVariant({{ $index }})" type="button" class="p-2 mb-0.5 text-red-400 hover:bg-red-500/10 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
                @error('variants') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="border-t border-slate-800"></div>
        @endif

        {{-- Stock thresholds --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Stock Thresholds</p>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Reorder Point</label>
                    <input wire:model="reorderPoint" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Min Stock</label>
                    <input wire:model="minStock" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Max Stock</label>
                    <input wire:model="maxStock" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800"></div>

        {{-- Visibility --}}
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Visibility & Status</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Status</label>
                    <select wire:model="status" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <input wire:model="isFeatured" type="checkbox" id="isFeatured" class="w-4 h-4 accent-indigo-500 rounded">
                    <label for="isFeatured" class="text-sm text-slate-300 cursor-pointer">Featured product</label>
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <input wire:model="isMarketplaceVisible" type="checkbox" id="isMarketplace" class="w-4 h-4 accent-indigo-500 rounded">
                    <label for="isMarketplace" class="text-sm text-slate-300 cursor-pointer">Show on marketplace</label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
            <a href="{{ route('admin.products.index') }}" wire:navigate class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</a>
            <button wire:click="save" class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">Create Product</button>
        </div>
    </div>
</div>
