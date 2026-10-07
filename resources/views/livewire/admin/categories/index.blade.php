<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Categories</h2>
            <p class="text-sm text-slate-400 mt-1">Manage product categories for all businesses. Raw Material and Saleable domains are <span class="text-amber-400 font-semibold">kept strictly separate</span>.</p>
        </div>
        @can('category.create')
        <button wire:click="openCreate"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Category
        </button>
        @endcan
    </div>

    @if(session('success'))<div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ session('error') }}</div>@endif

    {{-- Domain Info Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-sky-950/40 border border-sky-700/30 rounded-2xl p-4">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">🏭</span>
                <h3 class="text-sm font-bold text-sky-300">Manufacturing Categories</h3>
            </div>
            <p class="text-xs text-slate-400">Used internally for production. Never shown to customers.</p>
            <div class="flex flex-wrap gap-1.5 mt-3">
                <span class="px-2 py-0.5 rounded-full text-xs bg-sky-500/10 text-sky-400 border border-sky-500/20">Raw Material</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-violet-500/10 text-violet-400 border border-violet-500/20">Packaging Material</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-500/10 text-amber-400 border border-amber-500/20">Oil / Finished Product</span>
            </div>
        </div>
        <div class="bg-emerald-950/40 border border-emerald-700/30 rounded-2xl p-4">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">🛒</span>
                <h3 class="text-sm font-bold text-emerald-300">Saleable Product Categories</h3>
            </div>
            <p class="text-xs text-slate-400">Visible to customers. Each type has its own set of attributes.</p>
            <div class="flex flex-wrap gap-1.5 mt-3">
                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">📚 Book</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-teal-500/10 text-teal-400 border border-teal-500/20">✏️ Stationery</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-pink-500/10 text-pink-400 border border-pink-500/20">👗 Fashion</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-500/10 text-slate-400 border border-slate-500/20">📦 General</span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search category name..."
            class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
        </select>
        <select wire:model.live="type" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Types</option>
            <optgroup label="── Manufacturing ──">
                <option value="raw_material">Manufacturing → Raw Material</option>
                <option value="packing_material">Manufacturing → Packaging Material</option>
                <option value="oil">Manufacturing → Oil / Finished Product</option>
                <option value="finished_product">Manufacturing → Manufactured Product</option>
            </optgroup>
            <optgroup label="── Saleable ──">
                <option value="book">Saleable → Book</option>
                <option value="stationery">Saleable → Stationery</option>
                <option value="fashion">Saleable → Fashion</option>
                <option value="general">Saleable → General</option>
            </optgroup>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Domain</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Business</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Parent</th>
                        <th class="text-center px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Products</th>
                        <th class="text-center px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($categories as $cat)
                        @php
                            $isManufacturing = in_array($cat->type, ['raw_material', 'packing_material', 'oil', 'finished_product']);
                            $typeConfig = match($cat->type) {
                                'raw_material'     => ['color' => 'sky',    'icon' => '🌾', 'label' => 'Raw Material'],
                                'packing_material' => ['color' => 'violet', 'icon' => '📦', 'label' => 'Packaging'],
                                'oil'              => ['color' => 'amber',  'icon' => '🛢️', 'label' => 'Oil / Finished'],
                                'finished_product' => ['color' => 'amber',  'icon' => '🏭', 'label' => 'Manufactured'],
                                'book'             => ['color' => 'emerald','icon' => '📚', 'label' => 'Book'],
                                'stationery'       => ['color' => 'teal',   'icon' => '✏️', 'label' => 'Stationery'],
                                'fashion'          => ['color' => 'pink',   'icon' => '👗', 'label' => 'Fashion'],
                                default            => ['color' => 'slate',  'icon' => '📋', 'label' => 'General'],
                            };
                            $tc = $typeConfig['color'];
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ $typeConfig['icon'] }}</span>
                                    @if($cat->icon && $cat->icon !== $typeConfig['icon'])<span class="text-lg">{{ $cat->icon }}</span>@endif
                                    <div>
                                        <p class="font-semibold text-white">{{ $cat->name }}</p>
                                        <p class="text-xs text-slate-500 font-mono">{{ $cat->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($isManufacturing)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20">🏭 Manufacturing</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">🛒 Saleable</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $tc }}-500/10 text-{{ $tc }}-400 border border-{{ $tc }}-500/20">
                                    {{ $typeConfig['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-300 text-sm">{{ optional($cat->business)->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-400 text-sm">{{ optional($cat->parent)->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-center font-bold text-white">{{ $cat->products_count }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $cat->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700/50 text-slate-500' }}">
                                    {{ $cat->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    @can('category.update')
                                    <button wire:click="openEdit({{ $cat->id }})"
                                        class="p-2 rounded-lg text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endcan
                                    @can('category.delete')
                                    <button wire:click="confirmDelete({{ $cat->id }})"
                                        class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-16 text-center text-slate-600">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800">{{ $categories->links() }}</div>
    </div>

    {{-- Create / Edit Modal --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
                <h3 class="text-base font-bold text-white">{{ $editId ? 'Edit Category' : 'New Category' }}</h3>
                <button wire:click="$set('showForm',false)" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                @unless($editId)
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Business <span class="text-red-400">*</span></label>
                    <select wire:model.live="formBizId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select business...</option>
                        @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
                    </select>
                    @error('formBizId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endunless

                {{-- Domain Picker --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Category Domain & Type <span class="text-red-400">*</span></label>
                    <p class="text-xs text-slate-500 mb-3">Select whether this category is for Manufacturing (internal) or a Saleable product (customer-facing).</p>
                    <select wire:model.live="formType" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <optgroup label="🏭 Manufacturing (internal use only)">
                            <option value="raw_material">Raw Material — e.g. Palm Oil, Sunflower Seed</option>
                            <option value="packing_material">Packaging Material — e.g. Carton, Plastic Bottle</option>
                            <option value="oil">Oil / Finished Product</option>
                            <option value="finished_product">Manufactured Product</option>
                        </optgroup>
                        <optgroup label="🛒 Saleable (visible to customers)">
                            <option value="book">Book — e.g. Computer Science, Programming, AI</option>
                            <option value="stationery">Stationery — e.g. Notebook, Pen, Bag, Office</option>
                            <option value="fashion">Fashion — e.g. Jalabiya, Dress, Shirt, Trouser</option>
                            <option value="general">General Product</option>
                        </optgroup>
                    </select>
                </div>

                {{-- Domain Indicator Banner --}}
                @php $mfgTypes = ['raw_material','packing_material','oil','finished_product']; $isMfg = in_array($formType, $mfgTypes); @endphp
                <div class="rounded-xl px-4 py-2.5 text-xs font-semibold flex items-center gap-2 {{ $isMfg ? 'bg-sky-500/10 text-sky-300 border border-sky-500/20' : 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' }}">
                    @if($isMfg)
                        🏭 Manufacturing category — internal use, never visible to customers
                    @else
                        🛒 Saleable category — customer-facing storefront
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Name <span class="text-red-400">*</span></label>
                        <input wire:model="formName" type="text"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                        @error('formName') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Icon / Emoji</label>
                        <input wire:model="formIcon" type="text" placeholder="e.g. 📚"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Parent Category</label>
                        <select wire:model="formParent" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                            <option value="">None (root category)</option>
                            @foreach($parentOptions as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Default Unit</label>
                        <select wire:model="formUnit" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                            <option value="">None</option>
                            @foreach($categoryUnits as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Description</label>
                        <textarea wire:model="formDesc" rows="2"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none resize-none"></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input wire:model="formActive" type="checkbox" id="formActive" class="w-4 h-4 accent-indigo-500 rounded">
                        <label for="formActive" class="text-sm text-slate-300 cursor-pointer">Active</label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-3 sticky bottom-0 bg-slate-900">
                <button wire:click="$set('showForm',false)"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="save"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">
                    {{ $editId ? 'Save Changes' : 'Create Category' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirm --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-base font-bold text-white mb-2">Delete Category?</h3>
            <p class="text-sm text-slate-400 mb-6">Categories with products or subcategories cannot be deleted.</p>
            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showDeleteConfirm',false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="delete" class="px-5 py-2 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition">Delete</button>
            </div>
        </div>
    </div>
    @endif

</div>
