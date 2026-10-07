<div class="bg-slate-900 border border-slate-800 shadow-xl rounded-2xl h-full flex flex-col overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-800/80 flex flex-wrap items-center justify-between gap-4 bg-slate-900/60">
        <div>
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                User & Role Management
            </h3>
            <p class="text-[11px] text-slate-400">Quick Access Administrative Directory</p>
        </div>
        <div class="flex items-center space-x-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search accounts..." 
                class="rounded-xl border-slate-800 bg-slate-950 text-slate-200 text-xs px-3 py-1.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-500">
            <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3 py-1.5 rounded-xl shadow-lg shadow-indigo-600/30 transition border border-indigo-400/30">
                + Add User
            </button>
        </div>
    </div>
    
    <div class="flex-1 overflow-x-auto p-0">
        <table class="w-full divide-y divide-slate-800/80 text-left">
            <thead class="bg-slate-950/60 text-[10px] uppercase tracking-wider font-extrabold text-slate-400">
                <tr>
                    <th scope="col" class="px-6 py-3">User Profile</th>
                    <th scope="col" class="px-6 py-3">Email Address</th>
                    <th scope="col" class="px-6 py-3">Assigned Role</th>
                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-500 text-white flex items-center justify-center font-bold text-xs border border-indigo-400/30 shadow-md">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="font-bold text-white">
                                    {{ $user->name }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-slate-400 font-mono">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-[10px] text-slate-500 italic">No Role Assigned</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-right font-medium">
                            <a href="#" class="text-indigo-400 hover:text-indigo-300 transition">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                            No users found matching "<span class="text-slate-300 font-semibold">{{ $search }}</span>".
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-slate-950/60 px-6 py-3 border-t border-slate-800/80 text-center">
        <a href="#" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">View Full User Directory &rarr;</a>
    </div>
</div>
