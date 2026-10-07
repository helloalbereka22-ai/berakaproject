<div class="max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4 bg-slate-900 border border-slate-800/90 rounded-2xl p-6 shadow-xl">
        <a href="{{ route('admin.branches.index') }}" wire:navigate
            class="p-2.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 transition shadow-sm"
            title="Back to Branches">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Add New Branch</h2>
            <p class="text-xs text-slate-400 mt-1">Register a new retail branch, bookstore location, or factory plant for Al-Baraka businesses.</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="bg-slate-900 border border-slate-800/90 rounded-2xl p-6 shadow-xl space-y-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- 1. Business Entity --}}
            <div class="sm:col-span-2 space-y-1.5">
                <label for="businessId" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Business Entity <span class="text-red-400">*</span>
                </label>
                <select wire:model.live="businessId" id="businessId"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-sm font-semibold text-white focus:border-indigo-500 outline-none transition">
                    <option value="">-- Select Target Business --</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b['id'] }}">
                            {{ $b['name'] }} ({{ $b['code'] ?? 'BUS' }})
                        </option>
                    @endforeach
                </select>
                @error('businessId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 2. Branch Name --}}
            <div class="sm:col-span-2 space-y-1.5">
                <label for="name" class="block text-xs font-semibold text-slate-300">
                    Branch / Facility Name <span class="text-red-400">*</span>
                </label>
                <input wire:model="name" type="text" id="name" placeholder="e.g. Bookstore Branch 3 — Bole, Hawassa Distribution Hub, Factory Unit B"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 outline-none transition">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 3. Branch Code --}}
            <div class="space-y-1.5">
                <label for="code" class="block text-xs font-semibold text-slate-300">
                    Branch Identifier Code <span class="text-red-400">*</span>
                </label>
                <input wire:model="code" type="text" id="code" placeholder="e.g. BOOK-BR3, OIL-PLANT2"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono font-bold text-white uppercase placeholder-slate-600 focus:border-indigo-500 outline-none transition">
                @error('code') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 4. Status --}}
            <div class="space-y-1.5">
                <label for="status" class="block text-xs font-semibold text-slate-300">
                    Operational Status <span class="text-red-400">*</span>
                </label>
                <select wire:model="status" id="status"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 outline-none transition">
                    <option value="active">Active (Operational)</option>
                    <option value="inactive">Inactive (Pending / Suspended)</option>
                </select>
                @error('status') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 5. Branch Manager (Optional) --}}
            <div class="sm:col-span-2 space-y-1.5">
                <label for="managerId" class="block text-xs font-semibold text-slate-300">
                    Assigned Branch Manager (Optional)
                </label>
                <select wire:model="managerId" id="managerId"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 outline-none transition">
                    <option value="">-- No manager assigned yet --</option>
                    @foreach($managers as $m)
                        <option value="{{ $m['id'] }}">{{ $m['name'] }} ({{ $m['email'] }})</option>
                    @endforeach
                </select>
                @error('managerId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 6. Description & Location Notes --}}
            <div class="sm:col-span-2 space-y-1.5">
                <label for="description" class="block text-xs font-semibold text-slate-300">
                    Location Address & Description (Optional)
                </label>
                <textarea wire:model="description" rows="3" id="description" placeholder="Enter street address, building, city, phone number or location details..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none resize-none transition placeholder-slate-600"></textarea>
                @error('description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- Auto-Provision Notice --}}
        <div class="p-3.5 rounded-xl bg-indigo-950/30 border border-indigo-800/30 flex items-start gap-2.5 text-xs text-indigo-300">
            <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong class="font-bold text-indigo-200">Automatic Storage Provisioning:</strong> Creating this branch will automatically generate a linked storage warehouse (e.g. <em>[Branch Name] Stock</em>) so purchases, sales POS, and inventory movements work immediately.
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.branches.index') }}" wire:navigate
                class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition">
                Cancel
            </a>
            <button wire:click="save" wire:loading.attr="disabled" id="submit-branch-btn"
                class="px-6 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition flex items-center gap-2 disabled:opacity-50">
                <span wire:loading.remove>Save & Create Branch</span>
                <span wire:loading>Creating Branch...</span>
            </button>
        </div>

    </div>

</div>
