{{-- ══ MANUFACTURING REPORT ════════════════════════════════════════════════ --}}
@php
    $mfgProducts = collect($reportData['mfg_products'] ?? []);
    $totalMfgQty = $mfgProducts->sum('mfg_qty');
    $totalValue  = $mfgProducts->sum('total_value');
    $totalSeed   = $mfgProducts->sum('seed_qty_used');
@endphp

<div class="space-y-6 pt-4">

    {{-- Manufactured Products table --}}
    <div class="bg-slate-900 border border-violet-700/30 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white text-sm">🏭 Manufactured Products</h3>
                <p class="text-xs text-slate-500 mt-0.5">All products manufactured — quantities from manufacturer records</p>
            </div>
            <span class="text-xs text-slate-500">{{ $mfgProducts->count() }} product(s)</span>
        </div>

        @if($mfgProducts->isEmpty())
            <div class="py-16 text-center">
                <div class="text-5xl mb-4">🏭</div>
                <p class="text-slate-400 font-medium text-sm">No manufactured products found</p>
                <p class="text-slate-600 text-xs mt-1">Manufacturer must register production first</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Product</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-right px-5 py-3.5 font-bold">New Mfr</th>
                        <th class="text-right px-5 py-3.5 font-bold">Used Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Manufactured Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Seed Used</th>
                        <th class="text-right px-5 py-3.5 font-bold">Selling Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Value</th>
                        <th class="text-left px-5 py-3.5 font-bold">Username</th>
                        <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @foreach($mfgProducts as $i => $row)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white">{{ $row['item_name'] }}</p>
                            <p class="text-[10px] text-slate-500">{{ $row['business_name'] }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400">{{ $row['category_name'] }}</td>
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
                            <span class="font-black font-mono text-violet-400 text-base">{{ number_format($row['mfg_qty'], 2) }}</span>
                            <span class="text-slate-600 text-[10px] ml-1">{{ $row['unit'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-amber-400">
                            @if($row['seed_qty_used'] > 0)
                                {{ number_format($row['seed_qty_used'], 2) }}
                                <span class="text-slate-600 text-[10px]"> {{ $row['seed_unit'] }}</span>
                            @else
                                <span class="text-slate-600">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-slate-300">
                            {{ number_format($row['selling_price'] ?? 0, 2) }}
                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-emerald-300">
                            {{ number_format($row['total_value'] ?? 0, 2) }}
                            <span class="text-slate-600 text-[10px] font-normal"> ETB</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-slate-300 text-sm">{{ $row['manufactured_by_username'] ?? $row['manufactured_by'] ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3 border-l-2 border-slate-700">
                            <span class="inline-flex px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-500/50">
                                {{ $row['manufactured_by_branch'] ?? '—' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
