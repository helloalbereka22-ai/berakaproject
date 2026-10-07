<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Audit Logs</h2>
            <p class="text-sm text-slate-400 mt-1">Complete trail of all important system operations.</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search user, action, entity..."
                class="col-span-2 lg:col-span-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
            <select wire:model.live="actionFilter" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="transfer">Transfer</option>
                <option value="adjustment">Adjustment</option>
                <option value="role_assigned">Role Assigned</option>
            </select>
            <input wire:model.live="dateFrom" type="date"
                class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <input wire:model.live="dateTo" type="date"
                class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Entity</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Delete</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-sm font-semibold text-white">{{ optional($log->user)->name ?? 'System' }}</div>
                                <div class="text-xs text-slate-500">{{ optional($log->user)->email ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3">
                                @php
                                    $colors = ['created'=>'emerald','updated'=>'blue','deleted'=>'red','approved'=>'violet','rejected'=>'amber','login'=>'sky','logout'=>'slate','transfer'=>'indigo','adjustment'=>'orange','role_assigned'=>'pink'];
                                    $c = $colors[$log->action] ?? 'slate';
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-{{ $c }}-500/10 text-{{ $c }}-400 border border-{{ $c }}-500/20 uppercase tracking-wide">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-xs text-slate-400 font-mono">
                                    {{ class_basename($log->auditable_type ?? '') }}
                                    @if($log->auditable_id) <span class="text-slate-600">#{{ $log->auditable_id }}</span> @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-300 text-xs max-w-xs truncate">
                                {{ $log->description ?? '—' }}
                            </td>
    
                            <td class="px-5 py-3"><button wire:click="delete({{ $log->id }})" class="text-red-500 hover:underline">Delete</button></td>
                    </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-slate-600">No audit logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-800">{{ $logs->links() }}</div>
    </div>

</div>
