<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6 border-t-4 border-purple-600">
        <h2 class="text-xl font-bold text-gray-800 mb-4">IAM & Scoped Role Assignment</h2>

        @if (session()->has('message'))
            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="assignRole" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select wire:model="userId" class="w-full border rounded px-3 py-2">
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select wire:model="roleId" class="w-full border rounded px-3 py-2">
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Scope</label>
                <select wire:model.live="businessId" class="w-full border rounded px-3 py-2">
                    <option value="">Global / All Businesses</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch Scope</label>
                <select wire:model="branchId" class="w-full border rounded px-3 py-2" {{ empty($branches) ? 'disabled' : '' }}>
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 text-right">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded transition shadow">
                    Assign Scoped Role
                </button>
            </div>
        </form>
    </div>

    <!-- Assigned Roles Overview Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 bg-gray-50 border-b">
            <h3 class="font-bold text-gray-700">User Scoped Roles Directory</h3>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-xs font-semibold text-gray-600 uppercase border-b">
                    <th class="p-3">User</th>
                    <th class="p-3">Role</th>
                    <th class="p-3">Business Scope</th>
                    <th class="p-3">Branch Scope</th>
                </tr>
            </thead>
            <tbody class="divide-y text-sm">
                @foreach($users as $user)
                    @foreach($user->roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 font-medium">{{ $user->name }}</td>
                            <td class="p-3"><span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded font-semibold">{{ $role->name }}</span></td>
                            <td class="p-3">{{ $role->pivot->business_id ? \Albareka\Organization\Domain\Models\Business::find($role->pivot->business_id)?->name : 'Global' }}</td>
                            <td class="p-3">{{ $role->pivot->branch_id ? \Albareka\Organization\Domain\Models\Branch::find($role->pivot->branch_id)?->name : 'All Branches' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
