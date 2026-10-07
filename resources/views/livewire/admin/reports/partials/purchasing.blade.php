{{-- ══ PURCHASING REPORT ════════════════════════════════════════════════════ --}}
@php
    $rows   = $reportData['rows']   ?? collect();
    $totals = $reportData['totals'] ?? [];
@endphp

<div class="space-y-5 pt-4">

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Qty Purchased</p>
            <p class="text-xl font-extrabold text-sky-400 font-mono">{{ number_format($totals['quantity'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Purchase Cost</p>
            <p class="text-xl font-extrabold text-amber-400 font-mono">{{ number_format($totals['purchase_cost'] ?? 0, 2) }} <span class="text-xs font-normal text-slate-600">ETB</span></p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Selling Value</p>
            <p class="text-xl font-extrabold text-emerald-400 font-mono">{{ number_format($totals['selling_total'] ?? 0, 2) }} <span class="text-xs font-normal text-slate-600">ETB</span></p>
        </div>
    </div>

    {{-- Purchases table --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-slate-100 text-sm">Purchase Records</h3>
            <span class="text-xs text-slate-500">{{ $rows->count() }} record(s)</span>
        </div>

        @if($rows->isEmpty())
            <div class="py-16 text-center text-slate-500 text-sm">
                <svg class="w-8 h-8 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                No purchase records for the selected filters and date range.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                        <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                        <th class="text-right px-5 py-3.5 font-bold">Qty</th>
                        <th class="text-right px-5 py-3.5 font-bold">Purchase Price</th>
                        <th class="text-right px-5 py-3.5 font-bold">Total Cost</th>
                        <th class="text-right px-5 py-3.5 font-bold">Selling Price</th>
                        <th class="text-left px-5 py-3.5 font-bold">Registered By</th>
                        <th class="text-left px-5 py-3.5 font-bold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @foreach($rows as $row)
                    <tr class="hover:bg-slate-800/30 transition">

                        {{-- Item Name --}}
                        <td class="px-5 py-3">
                            <p class="font-semibold text-white">{{ $row->item_name }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $row->category_name }} · {{ $row->branch_name }}</p>
                        </td>

                        {{-- Qty --}}
                        <td class="px-5 py-3 text-right font-mono text-slate-200">
                            {{ number_format($row->quantity, 2) }}
                            <span class="text-slate-600 text-[10px]"> {{ $row->unit }}</span>
                        </td>

                        {{-- Purchase Price --}}
                        <td class="px-5 py-3 text-right font-mono text-amber-300">
                            {{ number_format($row->purchase_price, 2) }}
                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>

                        {{-- Total Cost --}}
                        <td class="px-5 py-3 text-right font-mono text-red-300 font-bold">
                            {{ number_format($row->total_price, 2) }}
                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>

                        {{-- Selling Price --}}
                        <td class="px-5 py-3 text-right font-mono text-emerald-300 font-bold">
                            {{ number_format($row->selling_price, 2) }}
                            <span class="text-slate-600 text-[10px]"> ETB</span>
                        </td>

                        {{-- Registered By --}}
                        <td class="px-5 py-3 text-xs text-slate-300">
                            {{ $row->registered_by ?? '—' }}
                        </td>

                        {{-- Date --}}
                        <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') }}
                        </td>

                    </tr>
                    @endforeach
                </tbody>

                {{-- Totals footer --}}
                <tfoot>
                    <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                        <td class="px-5 py-3.5 text-slate-400 uppercase tracking-widest">Total</td>
                        <td class="px-5 py-3.5 text-right font-mono text-sky-300">
                            {{ number_format($totals['quantity'] ?? 0, 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-amber-300">
                            {{ number_format($totals['purchase_cost'] ?? 0, 2) }}
                            <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-red-300">
                            {{ number_format($totals['purchase_cost'] ?? 0, 2) }}
                            <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-emerald-300">
                            {{ number_format($totals['selling_total'] ?? 0, 2) }}
                            <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                        </td>
                        <td class="px-5 py-3.5" colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
