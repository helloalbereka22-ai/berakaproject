<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.users.index') }}" wire:navigate class="p-2 rounded-xl text-slate-500 hover:text-white hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-white">Edit User</h2>
            <p class="text-sm text-slate-400 mt-0.5">{{ $editUser->name }}</p>
        </div>
    </div>

    @if(session('success'))<div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ session('error') }}</div>@endif

    {{-- Profile --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Profile Information</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                <input wire:model="name" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Email <span class="text-red-400">*</span></label>
                <input wire:model="email" type="email" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">New Password <span class="text-slate-600">(leave blank to keep current)</span></label>
                <input wire:model="password" type="password" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none">
                @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Status</label>
                <select wire:model="status" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end pt-2 border-t border-slate-800">
            <button wire:click="save" class="px-6 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition">Save Profile</button>
        </div>
    </div>

    {{-- Role Assignments --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Role Assignments</p>

        {{-- Current roles --}}
        @if(count($userRoles))
        <div class="space-y-2">
            @foreach($userRoles as $ur)
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50 border border-slate-700/50">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/15 text-indigo-300">{{ $ur['role_name'] }}</span>
                    @if($ur['business_id'])
                        <span class="text-xs text-slate-400">Business #{{ $ur['business_id'] }}</span>
                    @else
                        <span class="text-xs text-slate-500">Global scope</span>
                    @endif
                    @if($ur['branch_id'])
                        <span class="text-xs text-slate-400">/ Branch #{{ $ur['branch_id'] }}</span>
                    @endif
                </div>
                @can('role.update')
                <button wire:click="removeRole({{ array_search($ur, $userRoles) !== false ? collect($roles)->firstWhere('name', $ur['role_name'])['id'] ?? 0 : 0 }}, {{ $ur['business_id'] ?? 'null' }}, {{ $ur['branch_id'] ?? 'null' }})"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @endcan
            </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-slate-500">No roles assigned yet.</p>
        @endif

        {{-- Add new role --}}
        @can('role.update')
        <div class="border-t border-slate-800 pt-4">
            <p class="text-xs font-semibold text-slate-500 mb-3">Assign Additional Role</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select wire:model="newRoleId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                    <option value="">Select role...</option>
                    @foreach($roles as $r)<option value="{{ $r['id'] }}">{{ $r['name'] }}</option>@endforeach
                </select>
                <select wire:model.live="newBusinessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                    <option value="">Global scope</option>
                    @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
                </select>
                <select wire:model="newBranchId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500" {{ empty($branches) ? 'disabled' : '' }}>
                    <option value="">All branches</option>
                    @foreach($branches as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
                </select>
            </div>
            <div class="mt-3 flex justify-end">
                <button wire:click="addRole" class="px-5 py-2 rounded-xl text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition">Assign Role</button>
            </div>
        </div>
        @endcan
    </div>
</div>
