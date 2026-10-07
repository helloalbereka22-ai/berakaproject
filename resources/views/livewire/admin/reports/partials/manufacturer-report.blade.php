{{-- ══ MANUFACTURER REPORT ══════════════════════════════════════════════════ --}}
@php
    $rows   = $reportData['rows']   ?? collect();
    $totals = $reportData['totals'] ?? [];
@endphp

<div class="space-y-5 pt-4">

    {{-- ── Type / Category / Item selectors ─────────────────────────────── --}}
    <div class="bg-slate-900/70 border border-slate-800/60 rounded-2xl p-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">Manufacturer Filters</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Report Type --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Type</label>
                <select wire:model.live="mfrReportType"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                    <option value="product">Product</option>
                    <option value="purchase">Purchase</option>
                </select>
            </div>

            {{-- Category (context-aware) --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">
                    {{ $mfrReportType === 'product' ? 'Product Category' : 'Purchase Category' }}
                </label>
                <select wire:model.live="mfrCategoryId"
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500">
                    <option value="">All Categories</option>
                    @foreach($mfrCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Item --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">
                    {{ $mfrReportType === 'product' ? 'Product Item' : 'Purchase Item' }}
                </label>
                <select wire:model.live="mfrItemId"
                    @if(empty($mfrCategoryId)) disabled @endif
                    class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-500 disabled:opacity-40">
                    <option value="">{{ $mfrCategoryId ? 'All Items' : '— Select Category First —' }}</option>
                    @foreach($mfrItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- ── PRODUCT TABLE ──────────────────────────────────────────────────── --}}
    @if($mfrReportType === 'product')

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Quantity</p>
                <p class="text-xl font-extrabold text-sky-400 font-mono">
                    {{ number_format($totals['quantity'] ?? 0, 3) }}
                    <span class="text-xs font-normal text-slate-600">units</span>
                </p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Selling Price</p>
                <p class="text-xl font-extrabold text-emerald-400 font-mono">
                    {{ number_format($totals['total_selling'] ?? 0, 2) }}
                    <span class="text-xs font-normal text-slate-600">ETB</span>
                </p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="font-bold text-slate-100 text-sm">Product Report</h3>
                <span class="text-xs text-slate-500">{{ $rows->count() }} item(s)</span>
            </div>

            @if($rows->isEmpty())
                <div class="py-16 text-center text-slate-500 text-sm">
                    <svg class="w-8 h-8 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    No product data for the selected filters and date range.
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                            <th class="text-left px-5 py-3.5 font-bold">#</th>
                            <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                            <th class="text-left px-5 py-3.5 font-bold">Category</th>
                            <th class="text-right px-5 py-3.5 font-bold">Quantity</th>
                            <th class="text-right px-5 py-3.5 font-bold">Unit Price</th>
                            <th class="text-right px-5 py-3.5 font-bold">Total Selling Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @foreach($rows as $i => $row)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-3 text-slate-500 text-xs font-mono">{{ $i + 1 }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $row['item_name'] }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-slate-400">{{ $row['category_name'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-slate-200">
                                {{ number_format($row['quantity'], 3) }}
                                <span class="text-slate-600 text-[10px]"> {{ $row['unit'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-sky-300">
                                {{ number_format($row['unit_price'], 2) }}
                                <span class="text-slate-600 text-[10px]"> ETB</span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-emerald-300 font-bold">
                                {{ number_format($row['total_selling'], 2) }}
                                <span class="text-slate-600 text-[10px]"> ETB</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                            <td class="px-5 py-3.5 text-slate-400 uppercase tracking-widest" colspan="3">Total</td>
                            <td class="px-5 py-3.5 text-right font-mono text-sky-300">
                                {{ number_format($totals['quantity'] ?? 0, 3) }}
                            </td>
                            <td class="px-5 py-3.5"></td>
                            <td class="px-5 py-3.5 text-right font-mono text-emerald-300">
                                {{ number_format($totals['total_selling'] ?? 0, 2) }}
                                <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>

    {{-- ── PURCHASE TABLE ─────────────────────────────────────────────────── --}}
    @else

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Qty Sold</p>
                <p class="text-xl font-extrabold text-amber-400 font-mono">
                    {{ number_format($totals['total_qty'] ?? 0, 3) }}
                    <span class="text-xs font-normal text-slate-600">units</span>
                </p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Total Selling Amount</p>
                <p class="text-xl font-extrabold text-emerald-400 font-mono">
                    {{ number_format($totals['total_selling'] ?? 0, 2) }}
                    <span class="text-xs font-normal text-slate-600">ETB</span>
                </p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="font-bold text-slate-100 text-sm">Purchase Sales Report — Transferred Items Sold</h3>
                <span class="text-xs text-slate-500">{{ $rows->count() }} item(s)</span>
            </div>

            @if($rows->isEmpty())
                <div class="py-16 text-center text-slate-500 text-sm">
                    <svg class="w-8 h-8 mx-auto mb-3 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    No purchase sales data for the selected filters and date range.
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] uppercase tracking-widest text-slate-400">
                            <th class="text-left px-5 py-3.5 font-bold">#</th>
                            <th class="text-left px-5 py-3.5 font-bold">Item Name</th>
                            <th class="text-left px-5 py-3.5 font-bold">Category</th>
                            <th class="text-right px-5 py-3.5 font-bold">Qty Sold</th>
                            <th class="text-right px-5 py-3.5 font-bold">Avg Unit Price</th>
                            <th class="text-right px-5 py-3.5 font-bold">Total Selling</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @foreach($rows as $i => $row)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-5 py-3 text-slate-500 text-xs font-mono">{{ $i + 1 }}</td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-white">{{ $row->item_name }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-slate-400">{{ $row->category_name }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-amber-300 font-bold">
                                {{ number_format($row->total_qty, 3) }}
                                <span class="text-slate-600 text-[10px]"> {{ $row->unit }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-sky-300">
                                {{ number_format($row->avg_unit_price, 2) }}
                                <span class="text-slate-600 text-[10px]"> ETB</span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-emerald-300 font-bold">
                                {{ number_format($row->total_selling, 2) }}
                                <span class="text-slate-600 text-[10px]"> ETB</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-700 bg-slate-950/70 font-bold text-xs">
                            <td class="px-5 py-3.5 text-slate-400 uppercase tracking-widest" colspan="3">Total</td>
                            <td class="px-5 py-3.5 text-right font-mono text-amber-300">
                                {{ number_format($totals['total_qty'] ?? 0, 3) }}
                            </td>
                            <td class="px-5 py-3.5"></td>
                            <td class="px-5 py-3.5 text-right font-mono text-emerald-300">
                                {{ number_format($totals['total_selling'] ?? 0, 2) }}
                                <span class="text-slate-600 font-normal text-[10px]"> ETB</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>

    @endif

</div>
