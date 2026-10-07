<div class="bg-slate-900 border border-slate-800 shadow-xl rounded-2xl h-full flex flex-col overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-800/80 flex items-center justify-between bg-slate-900/60">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            System Activity Log Feed
        </h3>
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Realtime</span>
    </div>
    
    <div class="flex-1 overflow-y-auto px-6 py-5 max-h-[380px]">
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @foreach($activities as $index => $activity)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                                <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-slate-800" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start space-x-3.5">
                                <div class="relative">
                                    @if($activity['icon'] === 'sale')
                                        <span class="h-10 w-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center border border-emerald-500/30">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </span>
                                    @elseif($activity['icon'] === 'stock')
                                        <span class="h-10 w-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        </span>
                                    @elseif($activity['icon'] === 'approval')
                                        <span class="h-10 w-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </span>
                                    @elseif($activity['icon'] === 'user')
                                        <span class="h-10 w-10 rounded-xl bg-violet-500/20 text-violet-400 flex items-center justify-center border border-violet-500/30">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </span>
                                    @else
                                        <span class="h-10 w-10 rounded-xl bg-slate-800 text-slate-300 flex items-center justify-center border border-slate-700">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-100 leading-snug">{{ $activity['message'] }}</p>
                                    <div class="mt-1 flex items-center space-x-2 text-[11px] text-slate-400 font-mono">
                                        <span class="text-indigo-400 font-medium">{{ $activity['user'] }}</span>
                                        <span>&bull;</span>
                                        <span class="text-slate-500">{{ $activity['time'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
