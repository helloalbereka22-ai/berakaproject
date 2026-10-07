{{-- Raw Material Report --}}
<div class="space-y-5 pt-4">

    {{-- Summary Cards --}}
    @if($reportData->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Items</p>
            <p class="text-2xl font-extrabold text-sky-400">{{ $reportData->count() }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Purchased Quantity</p>
            <p class="text-2xl font-extrabold text-amber-400">{{ number_format($reportData->sum('purchased_qty'), 2) }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Consumed</p>
            <p class="text-2xl font-extrabold text-red-400">{{ number_format($reportData->sum('consumed_qty'), 2) }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Value (Remaining)</p>
            <p class="text-xl font-extrabold text-emerald-400">{{ number_format($reportData->sum('total_value'), 2) }}</p>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white">📉 Raw & Packing Materials</h3>
                <p class="text-xs text-slate-500 mt-0.5">Seeds, bottles, packing materials purchased and consumed</p>
            </div>
            <span class="text-xs text-slate-500">{{ $reportData->count() }} item(s)</span>
        </div>

        @if($reportData->count() === 0)
        <div class="py-16 text-center">
            <div class="text-5xl mb-4">📦</div>
            <p class="text-slate-400 font-medium">No raw materials found</p>
            <p class="text-slate-600 text-xs mt-1">Register purchases in Price Management first</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">#</th>
                        <th class="text-left px-5 py-3.5 font-bold">Branch</th>
                        <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                        <th class="text-left px-5 py-3.5 font-bold">Category</th>
                        <th class="text-right px-5 py-3.5 font-bold">Purchased Quantity</th>
                        <th class="text-right px-5 py-3.5 font-bold">Consumed Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Remaining Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Unit Cost</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Cost</th>
                        <th class="text-left px-5 py-3.5 font-bold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @foreach($reportData as $i => $row)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $i + 1 }}</td>
                        <td class="px-5 py-3 text-xs text-slate-400">{{ $row['branch_name'] }}</td>
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white">{{ $row['item_name'] }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400">{{ $row['category_name'] }}</td>
                        <td class="px-5 py-3 text-right font-mono text-sky-300">
                            {{ number_format($row['purchased_qty'], 2) }}
                            <span class="text-slate-600 text-[10px]"> {{ $row['unit'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-red-400">
                            {{ number_format($row['consumed_qty'], 2) }}
                            <span class="text-slate-600 text-[10px]"> {{ $row['unit'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold
                            {{ $row['remaining_qty'] <= 0 ? 'text-red-400' : ($row['remaining_qty'] < 10 ? 'text-amber-400' : 'text-emerald-400') }}">
                            {{ number_format($row['remaining_qty'], 2) }}
                            <span class="text-slate-600 text-[10px] font-normal"> {{ $row['unit'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-slate-300">
                            {{ number_format($row['unit_cost'], 2) }}
                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-emerald-300">
                            {{ number_format($row['total_value'], 2) }}
                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $row['date'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                        <td class="px-5 py-3.5 text-slate-400" colspan="4">Total</td>
                        <td class="px-5 py-3.5 text-right font-mono text-sky-300">
                            {{ number_format($reportData->sum('purchased_qty'), 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-red-400">
                            {{ number_format($reportData->sum('consumed_qty'), 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-emerald-400">
                            {{ number_format($reportData->sum('remaining_qty'), 2) }}
                        </td>
                        <td class="px-5 py-3.5"></td>
                        <td class="px-5 py-3.5 text-right font-mono text-emerald-300">
                            {{ number_format($reportData->sum('total_value'), 2) }}
                            <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
