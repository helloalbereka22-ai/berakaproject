<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.manufacturing.batches') }}" wire:navigate
           class="text-slate-500 hover:text-slate-300 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h2 class="text-2xl font-bold text-white">New Manufacturing Order</h2>
    </div>

    @if($errorMessage)
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ $errorMessage }}</div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">

        {{-- Business --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Business *</label>
            <select wire:model.live="business_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">— Select Business —</option>
                @foreach($businesses as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </select>
            @error('business_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Branch / Factory --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Factory / Branch *</label>
            <select wire:model.live="branch_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500" @disabled(!$business_id)>
                <option value="">— Select Factory —</option>
                @foreach($branches as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </select>
            @error('branch_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Oil Product --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Oil Product *</label>
            <select wire:model.live="product_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500" @disabled(!$business_id)>
                <option value="">— Select Product —</option>
                @foreach($products as $p)
                    <option value="{{ $p['id'] }}">{{ $p['name'] }}{{ $p['package_size'] ? ' ('.$p['package_size'].')' : '' }}</option>
                @endforeach
            </select>
            @error('product_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Package Size --}}
        @if(!empty($variants))
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Package Size</label>
            <select wire:model="package_size" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">— Any / All sizes —</option>
                @foreach($packageSizes as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Planned Quantity --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Planned Quantity *</label>
            <input wire:model="planned_quantity" type="number" step="0.001" min="0.001" placeholder="e.g. 1000"
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 outline-none focus:border-indigo-500">
            @error('planned_quantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Start Date *</label>
                <input wire:model="planned_start_date" type="date"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                @error('planned_start_date') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">End Date</label>
                <input wire:model="planned_end_date" type="date"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            </div>
        </div>

        {{-- Supervisor --}}
        @if(!empty($supervisors))
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Supervisor</label>
            <select wire:model="supervisor_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">— None —</option>
                @foreach($supervisors as $s)
                    <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Notes --}}
        <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Notes</label>
            <textarea wire:model="notes" rows="3" placeholder="Optional production notes…"
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 outline-none focus:border-indigo-500 resize-none"></textarea>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.manufacturing.batches') }}" wire:navigate
               class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">
                Cancel
            </a>
            <button wire:click="save" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold transition disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Create Order</span>
                <span wire:loading wire:target="save">Creating…</span>
            </button>
        </div>
    </div>
</div>
