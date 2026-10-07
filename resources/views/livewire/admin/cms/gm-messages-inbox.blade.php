
    <div class="space-y-6">
    {{-- Header --}}
    <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1">Customer Messages</p>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Messages Inbox</h1>
        <p class="text-sm text-slate-400 mt-1">Messages sent by visitors through the Contact Us form</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
        <span class="px-4 py-2 bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-sm font-bold rounded-xl">
            {{ $total }} {{ Str::plural('message', $total) }}
        </span>
    </div>

    {{-- Search --}}
    <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="Search by name, email, subject, message..."
               class="w-full pl-9 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-indigo-500 outline-none transition" />
    </div>

    {{-- Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">#</th>
                        <th class="text-left px-5 py-3.5 font-bold">Name</th>
                        <th class="text-left px-5 py-3.5 font-bold">Phone</th>
                        <th class="text-left px-5 py-3.5 font-bold">Email</th>
                        <th class="text-left px-5 py-3.5 font-bold">Message Preview</th>
                        <th class="text-left px-5 py-3.5 font-bold">Date</th>
                        <th class="text-center px-4 py-3.5 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($messages as $i => $msg)
                        @php
                            $name    = $msg->name    ?? '—';
                            $phone   = $msg->phone   ?? '—';
                            $email   = $msg->email   ?? '—';
                            $subject = $msg->subject  ?? '—';
                            $body    = $msg->message  ?? '';
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-3 text-slate-500 text-xs">{{ $messages->firstItem() + $i }}</td>
                            <td class="px-5 py-3"><p class="font-semibold text-white text-sm">{{ $name }}</p></td>
                            <td class="px-5 py-3 text-xs text-slate-400"><p class="font-semibold">{{ $phone }}</p></td>
                            <td class="px-5 py-3 text-xs text-indigo-400"><a href="mailto:{{ $email }}" class="hover:text-indigo-300 transition">{{ $email }}</a></td>
                            <td class="px-5 py-3 text-xs text-slate-500 max-w-xs">
                                @if($body)
                                    <span class="line-clamp-1">{{ Str::limit($body, 60) }}</span>
                                @else
                                    <span class="text-slate-600 italic">No message saved</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ \Carbon\Carbon::parse($msg->created_at)->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button wire:click="viewMessage({{ $msg->id }})" class="p-1.5 text-slate-500 hover:text-indigo-400 bg-slate-800 hover:bg-indigo-500/10 rounded-lg transition" title="View">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button wire:click="showDeleteConfirm({{ $msg->id }})" class="p-1.5 text-slate-500 hover:text-red-400 bg-slate-800 hover:bg-red-500/10 rounded-lg transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="text-4xl mb-3">📭</div>
                                <p class="text-slate-400 font-medium text-sm">No messages yet</p>
                                @if($search)
                                <button wire:click="$set('search','')" class="mt-3 text-xs text-indigo-400 hover:text-indigo-300 transition">Clear search</button>
                                @else
                                <p class="text-slate-600 text-xs mt-1">Messages from the Contact Us form will appear here</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($messages->hasPages())
            <div class="px-5 py-4 border-t border-slate-800">{{ $messages->links() }}</div>
        @endif
    </div>

    {{-- View message modal --}}
    @if($viewing)
        @php
            $vData = $viewing->data ?? [];
            $vName = $vData['name'] ?? '—';
            $vPhone = $vData['phone'] ?? '—';
            $vEmail = $vData['email'] ?? '—';
            $vSubject = $vData['subject'] ?? '—';
            $vBody = $vData['message'] ?? '';
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-800/50">
                    <h3 class="font-extrabold text-white text-lg">Message Details</h3>
                    <button wire:click="closeMessage" class="p-1.5 text-slate-400 hover:text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-800/60 rounded-xl p-4"><p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Name</p><p class="text-white font-semibold text-sm">{{ $vName }}</p></div>
                        <div class="bg-slate-800/60 rounded-xl p-4"><p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Phone</p><p class="text-slate-300 font-semibold text-sm">{{ $vPhone }}</p></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-800/60 rounded-xl p-4"><p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Email</p><a href="mailto:{{ $vEmail }}" class="text-indigo-400 hover:text-indigo-300 font-semibold text-sm transition break-all">{{ $vEmail }}</a></div>
                    </div>
                    <div class="bg-slate-800/60 rounded-xl p-4"><p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Subject</p><p class="text-white font-semibold text-sm">{{ $vSubject }}</p></div>
                    <div class="bg-slate-800/60 rounded-xl p-4"><p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Message</p>@if($vBody)<p class="text-slate-300 text-sm leading-relaxed whitespace-pre-line">{{ $vBody }}</p>@else<p class="text-slate-600 text-sm italic">Message body was not saved for this submission.</p>@endif</div>
                    <div class="grid grid-cols-2 gap-4"><div class="bg-slate-800/60 rounded-xl p-4"><p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Received</p><p class="text-slate-400 text-sm">{{ \Carbon\Carbon::parse($viewing->created_at)->format('d M Y \a\t H:i') }}</p></div></div>
                </div>
                <div class="px-6 py-4 border-t border-slate-800 flex items-center justify-between">
                    <a href="mailto:{{ $vEmail }}?subject=Re: {{ urlencode($vSubject) }}" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>Reply via Email</a>
                    <div class="flex gap-2"><button wire:click="deleteMessage" class="px-4 py-2 bg-red-600/20 hover:bg-red-600/40 border border-red-500/30 text-red-400 text-sm font-bold rounded-xl transition">Delete</button><button wire:click="closeMessage" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold rounded-xl transition">Close</button></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm modal --}}
    @if($confirmDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="bg-slate-900 border border-red-700/40 rounded-2xl p-7 w-full max-w-sm shadow-2xl text-center">
                <div class="w-14 h-14 rounded-full bg-red-500/15 flex items-center justify-center mx-auto mb-4"><svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
                <h3 class="font-extrabold text-white text-lg mb-2">Delete Message?</h3>
                <p class="text-slate-400 text-sm mb-6">This will permanently delete the message. This cannot be undone.</p>
                <div class="flex gap-3"><button wire:click="$set('confirmDelete', false)" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold rounded-xl transition">Cancel</button><button wire:click="deleteMessage" class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-xl transition">Delete</button></div>
            </div>
        </div>
    @endif
</div>
