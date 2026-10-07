<div class="space-y-6 max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Assign Roles to Users</h2>

        @if (session()->has('success'))
            <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="assignRole" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">User</label>
                    <select wire:model="selectedUserId" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('selectedUserId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select wire:model="selectedRoleId" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a role...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedRoleId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Business (Optional)</label>
                    <select wire:model.live="selectedBusinessId" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Global / No Business Scope</option>
                        @foreach($businesses as $business)
                            <option value="{{ $business->id }}">{{ $business->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if(count($branches) > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700">Branch (Optional)</label>
                    <select wire:model="selectedBranchId" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Branches / No Branch Scope</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                    Assign Role
                </button>
            </div>
        </form>
    </div>
</div>
