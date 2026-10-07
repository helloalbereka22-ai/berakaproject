<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.products.index') }}" wire:navigate class="p-2 rounded-xl text-slate-500 hover:text-white hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-white">Edit Book</h2>
            <p class="text-sm text-slate-400 mt-0.5">{{ $product->name }}</p>
        </div>
    </div>

    @if(session('success'))<div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>@endif

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Category <span class="text-red-400">*</span></label>
                <select wire:model="categoryId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                    @foreach($categories as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach
                </select>
                @error('categoryId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div class="sm:col-span-2 mt-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Book Details</p>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Title <span class="text-red-400">*</span></label>
                <input wire:model="name" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Author</label>
                <input wire:model="author" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('author') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Publisher</label>
                <input wire:model="publisher" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('publisher') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">ISBN (Barcode)</label>
                <input wire:model="barcode" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">SKU</label>
                <input wire:model="sku" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                @error('sku') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Edition</label>
                <input wire:model="edition" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('edition') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Language</label>
                <input wire:model="language" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('language') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Description</label>
                <textarea wire:model="description" rows="3" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none resize-none"></textarea>
            </div>
        </div>

        <div class="border-t pt-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Pricing</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Cost Price <span class="text-red-400">*</span></label>
                    <input wire:model="costPrice" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('costPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Selling Price <span class="text-red-400">*</span></label>
                    <input wire:model="sellingPrice" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('sellingPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Discount Price</label>
                    <input wire:model="discountPrice" type="number" step="0.01" min="0"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:border-indigo-500 outline-none">
                    @error('discountPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    @if((float)$discountPrice > 0 && (float)$discountPrice < (float)$sellingPrice)
                        <p class="text-emerald-400 text-xs mt-1 font-bold">Discount: {{ number_format((float)$sellingPrice - (float)$discountPrice, 2) }} ETB ({{ number_format(((float)$sellingPrice - (float)$discountPrice) / (float)$sellingPrice * 100, 2) }}%)</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800 pt-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Visibility & Status</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Status</label>
                    <select wire:model="status" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-slate-800">
            <a href="{{ route('admin.products.index') }}" wire:navigate class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</a>
            <button wire:click="save" class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">Save Changes</button>
        </div>
    </div>
</div>
