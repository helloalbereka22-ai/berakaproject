{{-- GM Manufacturing Report — all products manufactured by the manufacturer --}}
<div class="space-y-5 pt-4">

    @php
        $rows = $reportData instanceof \Illuminate\Support\Collection ? $reportData : collect($reportData);
    @endphp

    {{-- Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white text-sm">🏭 Manufactured Products</h3>
                <p class="text-xs text-slate-500 mt-0.5">All products manufactured — quantities from manufacturer records</p>
            </div>
            <span class="text-xs text-slate-500">{{ $rows->count() }} product(s)</span>
        </div>

        @if($rows->isEmpty())
            <div class="py-16 text-center">
                <div class="text-5xl mb-4">🏭</div>
                <p class="text-slate-400 font-medium text-sm">No manufactured products found</p>
                <p class="text-slate-600 text-xs mt-1">Manufacturer must register production first</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-right px-5 py-3.5 font-bold">New Mfr</th>
                        <th class="text-right px-5 py-3.5 font-bold">Used Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Manufactured Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Seed Used</th>
                        <th class="text-left px-5 py-3.5 font-bold">Username</th>
                        <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                        <th class="text-center px-5 py-3.5 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @foreach($rows as $row)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white">{{ $row['item_name'] }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-slate-700/50 text-slate-300 border border-slate-600/50">
                                {{ $row['category_name'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-mono text-purple-400 font-semibold">
                                {{ number_format($row['new_mfr_quantity'] ?? 0, 3) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-mono text-orange-400 font-semibold">
                                {{ number_format($row['used_seed_qty'] ?? 0, 3) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-mono text-blue-400 font-semibold">
                                {{ number_format($row['mfg_qty'], 3) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if($row['seed_qty_used'] > 0)
                                <div class="flex flex-col items-end">
                                    <span class="font-mono text-amber-400 font-semibold">
                                        {{ number_format($row['seed_qty_used'], 3) }}
                                        <span class="text-slate-500 text-xs font-normal"> {{ $row['seed_unit'] }}</span>
                                    </span>
                                    @if($row['seed_name'] && $row['seed_name'] !== '—')
                                    <span class="text-[10px] text-slate-500 mt-0.5">{{ $row['seed_name'] }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-600">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-slate-300 text-sm">{{ $row['manufactured_by_username'] ?? $row['manufactured_by'] ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-slate-300 text-sm">{{ $row['manufactured_by_branch'] ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button"
                                    class="p-2 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 transition-colors"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                {{-- Delete button hidden --}}
                                {{-- <button type="button"
                                    class="p-2 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 transition-colors"
                                    title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button> --}}
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
